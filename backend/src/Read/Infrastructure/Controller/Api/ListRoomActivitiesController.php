<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListRoomActivities\ListRoomActivitiesQuery;
use App\Read\Application\Query\ListRoomActivities\ListRoomActivitiesQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Actividades de sala')]
class ListRoomActivitiesController extends AbstractController
{
    public function __construct(
        private ListRoomActivitiesQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/rooms/{roomId}/activities', name: 'api_room_activities_list', methods: ['GET'])]
    #[IsGranted('room_activities.manage')]
    #[OA\Get(
        path: '/api/rooms/{roomId}/activities',
        summary: 'Listar las actividades agendadas de una sala en un rango de fechas',
        description: 'Requiere el permiso "room_activities.manage" (Admin y Coordinador).',
        tags: ['Actividades de sala'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'roomId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de actividades de la sala'),
            new OA\Response(response: 400, description: 'Fechas inválidas'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(string $roomId, Request $request): JsonResponse
    {
        try {
            $from = new \DateTimeImmutable((string) $request->query->get('from', ''));
            $to = new \DateTimeImmutable((string) $request->query->get('to', ''));
        } catch (\Exception) {
            return $this->apiResponse->error('domain.shift.invalid_date', Response::HTTP_BAD_REQUEST);
        }

        $response = ($this->handler)(new ListRoomActivitiesQuery($roomId, $from, $to));

        return $this->apiResponse->success($response->serialize());
    }
}
