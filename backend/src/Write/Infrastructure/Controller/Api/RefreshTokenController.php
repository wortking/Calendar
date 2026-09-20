<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\RefreshToken\RefreshTokenCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Autenticación')]
class RefreshTokenController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $refreshTokenLimiter
    ) {}

    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[OA\Post(
        path: '/api/token/refresh',
        summary: 'Renovar el token JWT usando un refresh token',
        description: 'Canjea un refresh token (obtenido en el login) por un nuevo JWT. El refresh token es de un solo uso: cada llamada devuelve también uno nuevo (rotación).',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token renovado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'refreshToken', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Refresh token inválido, caducado o ya usado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = $this->refreshTokenLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new RefreshTokenCommand((string) ($data['refreshToken'] ?? ''));

        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.token.refreshed');
    }
}
