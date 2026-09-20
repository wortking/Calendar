<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListCompanies\ListCompaniesQuery;
use App\Read\Application\Query\ListCompanies\ListCompaniesQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Empresas')]
class ListCompaniesController extends AbstractController
{
    public function __construct(
        private ListCompaniesQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/companies', name: 'api_companies_list', methods: ['GET'])]
    #[IsGranted('companies.view')]
    #[OA\Get(
        path: '/api/companies',
        summary: 'Listar empresas',
        description: 'Requiere el permiso "companies.view".',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de empresas'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $response = ($this->handler)(new ListCompaniesQuery());

        return $this->apiResponse->success($response->serialize());
    }
}
