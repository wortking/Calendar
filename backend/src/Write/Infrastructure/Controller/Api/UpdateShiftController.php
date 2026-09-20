<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\UpdateShift\UpdateShiftCommand;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Turnos')]
class UpdateShiftController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/shifts/{shiftId}', name: 'api_shifts_update', methods: ['PATCH'])]
    #[IsGranted('shifts.manage')]
    #[OA\Patch(
        path: '/api/shifts/{shiftId}',
        summary: 'Editar un turno (horario y/o el empleado asignado)',
        description: 'Requiere el permiso "shifts.manage". Un coordinador solo puede editar turnos de su departamento, y solo puede reasignarlos a otro usuario de su mismo departamento.',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'shiftId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['userId', 'startAt', 'endAt'],
                properties: [
                    new OA\Property(property: 'userId', type: 'string'),
                    new OA\Property(property: 'startAt', type: 'string', format: 'date-time', example: '2026-09-22T13:00:00'),
                    new OA\Property(property: 'endAt', type: 'string', format: 'date-time', example: '2026-09-22T15:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Turno actualizado'),
            new OA\Response(response: 400, description: 'Error de validación (fechas inválidas o rango inválido)'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El turno o el usuario no existen'),
        ]
    )]
    public function __invoke(string $shiftId, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdateShiftCommand(
            $shiftId,
            (string) ($data['userId'] ?? ''),
            (string) ($data['startAt'] ?? ''),
            (string) ($data['endAt'] ?? ''),
            $currentUser->getId()
        );

        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException) {
                $status = match (true) {
                    in_array($previous->getTranslationKey(), ['handler.shift.not_found', 'handler.user.not_found'], true) => Response::HTTP_NOT_FOUND,
                    str_starts_with($previous->getTranslationKey(), 'handler.access_denied.') => Response::HTTP_FORBIDDEN,
                    default => Response::HTTP_BAD_REQUEST,
                };

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.shift.updated');
    }
}
