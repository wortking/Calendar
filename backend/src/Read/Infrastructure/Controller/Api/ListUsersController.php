<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListUsers\ListUsersQuery;
use App\Read\Application\Query\ListUsers\ListUsersQueryHandler;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Administración')]
class ListUsersController extends AbstractController
{
    public function __construct(
        private ListUsersQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('users.view')]
    #[OA\Get(
        path: '/api/users',
        summary: 'Listar usuarios',
        description: 'Requiere el permiso "users.view".',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'email', in: 'query', required: false, description: 'Filtra por coincidencia parcial de email', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'departmentId', in: 'query', required: false, description: 'Filtra por departamento', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de usuarios'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $email = $request->query->get('email');
        $departmentId = $request->query->get('departmentId');

        $response = ($this->handler)(new ListUsersQuery(
            $currentUser->getId(),
            $page,
            $limit,
            '' !== $email ? $email : null,
            '' !== $departmentId ? $departmentId : null
        ));

        return $this->apiResponse->success($response->serialize());
    }
}
