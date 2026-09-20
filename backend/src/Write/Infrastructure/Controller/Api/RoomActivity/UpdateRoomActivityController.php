<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api\RoomActivity;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\UpdateRoomActivity\UpdateRoomActivityCommand;
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

#[OA\Tag(name: 'Actividades de sala')]
class UpdateRoomActivityController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/room-activities/{id}', name: 'api_room_activities_update', methods: ['PATCH'])]
    #[IsGranted('room_activities.manage')]
    #[OA\Patch(
        path: '/api/room-activities/{id}',
        summary: 'Editar una actividad de sala',
        description: 'Requiere el permiso "room_activities.manage" (Admin y Coordinador).',
        tags: ['Actividades de sala'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['roomId', 'activityTypeId', 'userId', 'startAt', 'endAt'],
                properties: [
                    new OA\Property(property: 'roomId', type: 'string'),
                    new OA\Property(property: 'activityTypeId', type: 'string'),
                    new OA\Property(property: 'userId', type: 'string'),
                    new OA\Property(property: 'startAt', type: 'string', format: 'date-time', example: '2026-09-22T10:00:00'),
                    new OA\Property(property: 'endAt', type: 'string', format: 'date-time', example: '2026-09-22T11:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Actividad de sala actualizada'),
            new OA\Response(response: 400, description: 'Error de validación (rango inválido, sala/actividad/usuario ocupados o incompatibles)'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'La actividad de sala, la sala, el tipo de actividad o el usuario no existen'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdateRoomActivityCommand(
            $id,
            (string) ($data['roomId'] ?? ''),
            (string) ($data['activityTypeId'] ?? ''),
            (string) ($data['userId'] ?? ''),
            (string) ($data['startAt'] ?? ''),
            (string) ($data['endAt'] ?? '')
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
                    in_array($previous->getTranslationKey(), ['handler.room_activity.not_found', 'handler.room.not_found', 'handler.activity_type.not_found', 'handler.user.not_found'], true) => Response::HTTP_NOT_FOUND,
                    default => Response::HTTP_BAD_REQUEST,
                };

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.room_activity.updated');
    }
}
