<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\ResetPassword\ResetPasswordCommand;
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
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $resetPasswordLimiter
    ) {}

    #[Route('/api/reset-password', name: 'api_reset_password', methods: ['POST'])]
    #[OA\Post(
        path: '/api/reset-password',
        summary: 'Restablecer la contraseña con el código recibido por email',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code', 'newPassword'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@bicycle-sale.test'),
                    new OA\Property(property: 'code', type: 'string', example: '48213'),
                    new OA\Property(property: 'newPassword', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Contraseña restablecida'),
            new OA\Response(response: 400, description: 'Código inválido/caducado o error de validación'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = $this->resetPasswordLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new ResetPasswordCommand(
            (string) ($data['email'] ?? ''),
            (string) ($data['code'] ?? ''),
            (string) ($data['newPassword'] ?? '')
        );

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

        return $this->apiResponse->success(null, 'controller.password.reset');
    }
}
