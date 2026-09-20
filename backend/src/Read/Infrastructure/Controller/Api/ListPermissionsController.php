<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListPermissions\ListPermissionsQuery;
use App\Read\Application\Query\ListPermissions\ListPermissionsQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Administración')]
class ListPermissionsController extends AbstractController
{
    public function __construct(
        private ListPermissionsQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/permissions', name: 'api_permissions_list', methods: ['GET'])]
    #[IsGranted('permissions.manage')]
    #[OA\Get(
        path: '/api/permissions',
        summary: 'Listar permisos',
        description: 'Requiere el permiso "permissions.manage".',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de permisos'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $response = ($this->handler)(new ListPermissionsQuery());

        return $this->apiResponse->success($response->serialize());
    }
}
