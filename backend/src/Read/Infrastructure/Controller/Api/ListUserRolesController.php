<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListUserRoles\ListUserRolesQuery;
use App\Read\Application\Query\ListUserRoles\ListUserRolesQueryHandler;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Usuarios')]
class ListUserRolesController extends AbstractController
{
    public function __construct(
        private ListUserRolesQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{id}/roles', name: 'api_users_list_roles', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}/roles',
        summary: 'Listar los roles y permisos de un usuario',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Roles y permisos del usuario'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El usuario no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $id && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('controller.access_denied.view_other_user');
        }

        try {
            $response = ($this->handler)(new ListUserRolesQuery($id));
        } catch (\InvalidArgumentException $e) {
            return $this->apiResponse->error($e, Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse->success($response->serialize());
    }
}
