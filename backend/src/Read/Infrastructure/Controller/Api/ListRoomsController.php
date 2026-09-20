<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListRooms\ListRoomsQuery;
use App\Read\Application\Query\ListRooms\ListRoomsQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Salas')]
class ListRoomsController extends AbstractController
{
    public function __construct(
        private ListRoomsQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/rooms', name: 'api_rooms_list', methods: ['GET'])]
    #[IsGranted('rooms.view')]
    #[OA\Get(
        path: '/api/rooms',
        summary: 'Listar salas',
        description: 'Requiere el permiso "rooms.view". Sin "companyId" trae todas.',
        tags: ['Salas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'companyId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de salas'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $request->query->get('companyId');

        $response = ($this->handler)(new ListRoomsQuery('' !== $companyId ? $companyId : null));

        return $this->apiResponse->success($response->serialize());
    }
}
