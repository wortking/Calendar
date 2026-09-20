<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListEligibleUsersForRoomActivity\ListEligibleUsersForRoomActivityQuery;
use App\Read\Application\Query\ListEligibleUsersForRoomActivity\ListEligibleUsersForRoomActivityQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Actividades de sala')]
class ListEligibleUsersForRoomActivityController extends AbstractController
{
    public function __construct(
        private ListEligibleUsersForRoomActivityQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/rooms/{roomId}/eligible-users', name: 'api_room_activities_eligible_users', methods: ['GET'])]
    #[IsGranted('room_activities.manage')]
    #[OA\Get(
        path: '/api/rooms/{roomId}/eligible-users',
        summary: 'Listar empleados con turno en un horario exacto, disponibles para una actividad de sala',
        description: 'Requiere el permiso "room_activities.manage" (Admin y Coordinador). Excluye a quienes ya estén agendados en otra actividad de sala en ese horario.',
        tags: ['Actividades de sala'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'roomId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'startAt', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'endAt', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'excludeRoomActivityId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de empleados disponibles'),
            new OA\Response(response: 400, description: 'Fechas inválidas'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(string $roomId, Request $request): JsonResponse
    {
        try {
            $startAt = new \DateTimeImmutable((string) $request->query->get('startAt', ''));
            $endAt = new \DateTimeImmutable((string) $request->query->get('endAt', ''));
        } catch (\Exception) {
            return $this->apiResponse->error('domain.shift.invalid_date', Response::HTTP_BAD_REQUEST);
        }

        $excludeRoomActivityId = $request->query->get('excludeRoomActivityId');

        $response = ($this->handler)(new ListEligibleUsersForRoomActivityQuery(
            $startAt,
            $endAt,
            '' !== $excludeRoomActivityId ? $excludeRoomActivityId : null
        ));

        return $this->apiResponse->success($response->serialize());
    }
}
