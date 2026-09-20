<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\RevokeDepartmentFromUser\RevokeDepartmentFromUserCommand;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Usuarios')]
class RevokeDepartmentFromUserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{id}/departments/{departmentId}', name: 'api_users_revoke_department', methods: ['DELETE'])]
    #[IsGranted('users.edit')]
    #[OA\Delete(
        path: '/api/users/{id}/departments/{departmentId}',
        summary: 'Quitar un departamento a un usuario',
        description: 'Requiere el permiso "users.edit" (lo tiene por defecto ROLE_ADMIN).',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Departamento revocado'),
            new OA\Response(response: 403, description: 'No autorizado (falta el permiso users.edit)'),
            new OA\Response(response: 404, description: 'El usuario o el departamento no existen'),
        ]
    )]
    public function __invoke(string $id, string $departmentId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->messageBus->dispatch(new RevokeDepartmentFromUserCommand($id, $departmentId, $currentUser->getId()));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException && str_starts_with($previous->getTranslationKey(), 'handler.access_denied.')) {
                return $this->apiResponse->error($previous, Response::HTTP_FORBIDDEN);
            }

            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.department.revoked');
    }
}
