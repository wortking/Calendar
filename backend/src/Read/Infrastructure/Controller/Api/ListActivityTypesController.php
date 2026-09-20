<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListActivityTypes\ListActivityTypesQuery;
use App\Read\Application\Query\ListActivityTypes\ListActivityTypesQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Actividades')]
class ListActivityTypesController extends AbstractController
{
    public function __construct(
        private ListActivityTypesQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/activity-types', name: 'api_activity_types_list', methods: ['GET'])]
    #[IsGranted('activity_types.view')]
    #[OA\Get(
        path: '/api/activity-types',
        summary: 'Listar tipos de actividad',
        description: 'Requiere el permiso "activity_types.view". "departmentId" acepta uno o varios ids separados por coma; sin él, trae todos.',
        tags: ['Actividades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'departmentId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de tipos de actividad'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $departmentIdParam = $request->query->get('departmentId');
        $departmentIds = null !== $departmentIdParam
            ? array_values(array_filter(array_map('trim', explode(',', $departmentIdParam))))
            : null;

        $response = ($this->handler)(new ListActivityTypesQuery($departmentIds));

        return $this->apiResponse->success($response->serialize());
    }
}
