<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListMyShifts\ListMyShiftsQuery;
use App\Read\Application\Query\ListMyShifts\ListMyShiftsQueryHandler;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Turnos de un empleado puntual, para que Admin/Coordinador puedan ver el
 * resumen de horas de cualquiera (Admin) o de su propio departamento
 * (Coordinador) desde "Mis horas", sin necesitar sus credenciales.
 */
#[OA\Tag(name: 'Turnos')]
class ListUserShiftsController extends AbstractController
{
    public function __construct(
        private ListMyShiftsQueryHandler $handler,
        private DepartmentScopeGuard $scopeGuard,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{userId}/shifts', name: 'api_users_shifts_list', methods: ['GET'])]
    #[IsGranted('shifts.manage')]
    #[OA\Get(
        path: '/api/users/{userId}/shifts',
        summary: 'Listar los turnos de un empleado puntual',
        description: 'Requiere el permiso "shifts.manage". Un coordinador solo puede consultar empleados de su propio departamento.',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de turnos'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(string $userId, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->scopeGuard->assertCanManageUser($currentUser->getId(), $userId);
        } catch (TranslatableException $e) {
            return $this->apiResponse->error($e, Response::HTTP_FORBIDDEN);
        }

        $from = $this->parseDateOrDefault($request->query->get('start'), '-30 days');
        $to = $this->parseDateOrDefault($request->query->get('end'), '+90 days');

        $response = ($this->handler)(new ListMyShiftsQuery($userId, $from, $to));

        return $this->apiResponse->success($response->serialize());
    }

    private function parseDateOrDefault(?string $value, string $defaultModifier): \DateTimeImmutable
    {
        if (null !== $value) {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Exception) {
                // Ignora un query param malformado y cae al valor por defecto.
            }
        }

        return (new \DateTimeImmutable('today'))->modify($defaultModifier);
    }
}
