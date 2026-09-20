<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\GetRole\GetRoleQuery;
use App\Read\Application\Query\GetRole\GetRoleQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Administración')]
class GetRoleController extends AbstractController
{
    public function __construct(
        private GetRoleQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/roles/{id}', name: 'api_roles_get', methods: ['GET'])]
    #[IsGranted('roles.manage')]
    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Detalle de un rol (con los ids de sus permisos)',
        description: 'Requiere el permiso "roles.manage".',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del rol'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El rol no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $response = ($this->handler)(new GetRoleQuery($id));
        } catch (\InvalidArgumentException $e) {
            return $this->apiResponse->error($e, Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse->success($response->serialize());
    }
}
