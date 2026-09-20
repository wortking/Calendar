<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListDepartments\ListDepartmentsQuery;
use App\Read\Application\Query\ListDepartments\ListDepartmentsQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Departamentos')]
class ListDepartmentsController extends AbstractController
{
    public function __construct(
        private ListDepartmentsQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/departments', name: 'api_departments_list', methods: ['GET'])]
    #[IsGranted('departments.view')]
    #[OA\Get(
        path: '/api/departments',
        summary: 'Listar departamentos',
        description: 'Requiere el permiso "departments.view".',
        tags: ['Departamentos'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de departamentos'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $response = ($this->handler)(new ListDepartmentsQuery());

        return $this->apiResponse->success($response->serialize());
    }
}
