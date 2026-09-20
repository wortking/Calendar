<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListShiftsByDate\ListShiftsByDateQuery;
use App\Read\Application\Query\ListShiftsByDate\ListShiftsByDateQueryHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Turnos')]
class ListShiftsByDateController extends AbstractController
{
    public function __construct(
        private ListShiftsByDateQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/shifts', name: 'api_shifts_list_by_date', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/shifts',
        summary: 'Listar los turnos de todos los usuarios en una fecha',
        description: 'Devuelve, para el día indicado (o hoy si no se pasa "date"), los turnos de todos los usuarios junto con su email/nombre.',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-09-19')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de turnos del día'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $dateParam = $request->query->get('date');

        try {
            $day = null !== $dateParam ? new \DateTimeImmutable($dateParam) : new \DateTimeImmutable('today');
        } catch (\Exception) {
            $day = new \DateTimeImmutable('today');
        }

        $from = $day->setTime(0, 0, 0);
        $to = $from->modify('+1 day');

        $response = ($this->handler)(new ListShiftsByDateQuery($from, $to));

        return $this->apiResponse->success($response->serialize());
    }
}
