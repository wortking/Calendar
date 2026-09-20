<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Domain\Model\User;
use App\Write\Application\UseCase\ResendVerificationEmail\ResendVerificationEmailCommand;
use App\Write\Application\UseCase\ResendVerificationEmail\ResendVerificationEmailResponse;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Autenticación')]
class ResendVerificationEmailController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $resendVerificationEmailLimiter
    ) {}

    #[Route('/api/resend-verification-email', name: 'api_resend_verification_email', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/resend-verification-email',
        summary: 'Reenviar el email de verificación al usuario autenticado',
        description: 'No hace nada si el email ya está verificado.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Email reenviado (o ya estaba verificado)'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $limit = $this->resendVerificationEmailLimiter->create($currentUser->getId())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        try {
            $envelope = $this->messageBus->dispatch(new ResendVerificationEmailCommand($currentUser->getId()));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        /** @var ResendVerificationEmailResponse|null $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        $message = $result?->alreadyVerified
            ? 'controller.email_verification.already_verified'
            : 'controller.email_verification.resent';

        return $this->apiResponse->success(null, $message);
    }
}
