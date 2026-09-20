<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\Logout\LogoutCommand;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Autenticación')]
class LogoutController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse,
        private Security $security,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el JWT actual (queda inválido aunque no haya caducado) y, si se envía, el refresh token asociado.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->jwtManager->decode($this->security->getToken());

        $jti = (string) ($payload['jti'] ?? '');
        $exp = (int) ($payload['exp'] ?? 0);
        $ttlSeconds = max(0, $exp - time());

        $data = json_decode($request->getContent(), true) ?? [];
        $refreshToken = isset($data['refreshToken']) ? (string) $data['refreshToken'] : null;

        $this->messageBus->dispatch(new LogoutCommand($jti, $ttlSeconds, $refreshToken));

        return $this->apiResponse->success(null, 'controller.logout.success');
    }
}
