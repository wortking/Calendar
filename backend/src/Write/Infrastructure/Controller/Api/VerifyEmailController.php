<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\VerifyEmail\VerifyEmailCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Autenticación')]
class VerifyEmailController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $verifyEmailLimiter
    ) {}

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['POST'])]
    #[OA\Post(
        path: '/api/verify-email',
        summary: 'Confirmar el email con el token recibido por correo',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email verificado'),
            new OA\Response(response: 400, description: 'Token inválido/caducado o error de validación'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = $this->verifyEmailLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new VerifyEmailCommand((string) ($data['token'] ?? ''));

        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.email.verified');
    }
}
