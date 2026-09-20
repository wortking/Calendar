<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListRoles\ListRolesQuery;
use App\Read\Application\Query\ListRoles\ListRolesQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Administración')]
class ListRolesController extends AbstractController
{
    public function __construct(
        private ListRolesQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/roles', name: 'api_roles_list', methods: ['GET'])]
    #[IsGranted('roles.manage')]
    #[OA\Get(
        path: '/api/roles',
        summary: 'Listar roles',
        description: 'Requiere el permiso "roles.manage".',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de roles'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $response = ($this->handler)(new ListRolesQuery());

        return $this->apiResponse->success($response->serialize());
    }
}
