<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\ListDepartmentShifts\ListDepartmentShiftsQuery;
use App\Read\Application\Query\ListDepartmentShifts\ListDepartmentShiftsQueryHandler;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Turnos')]
class ListDepartmentShiftsController extends AbstractController
{
    public function __construct(
        private ListDepartmentShiftsQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/me/department-shifts', name: 'api_me_department_shifts_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/me/department-shifts',
        summary: 'Listar los turnos de mis compañeros de departamento',
        description: 'Devuelve los turnos (con email/nombre) de todos los usuarios que comparten al menos un departamento con el usuario autenticado, incluido él mismo. Si no tiene departamento, devuelve solo los suyos.',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de turnos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $from = $this->parseDateOrDefault($request->query->get('start'), '-30 days');
        $to = $this->parseDateOrDefault($request->query->get('end'), '+90 days');

        $response = ($this->handler)(new ListDepartmentShiftsQuery($currentUser->getId(), $from, $to));

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
