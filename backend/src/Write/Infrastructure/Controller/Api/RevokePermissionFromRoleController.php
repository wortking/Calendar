<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\RevokePermissionFromRole\RevokePermissionFromRoleCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Administración')]
class RevokePermissionFromRoleController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/roles/{id}/permissions/{permissionId}', name: 'api_roles_revoke_permission', methods: ['DELETE'])]
    #[IsGranted('roles.manage')]
    #[OA\Delete(
        path: '/api/roles/{id}/permissions/{permissionId}',
        summary: 'Quitar un permiso de un rol',
        description: 'Requiere el permiso "roles.manage".',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'permissionId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permiso revocado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El rol o el permiso no existen'),
        ]
    )]
    public function __invoke(string $id, string $permissionId): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new RevokePermissionFromRoleCommand($id, $permissionId));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.role.permission_revoked');
    }
}
