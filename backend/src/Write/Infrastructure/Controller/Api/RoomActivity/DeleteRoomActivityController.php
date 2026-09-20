<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api\RoomActivity;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeleteRoomActivity\DeleteRoomActivityCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Actividades de sala')]
class DeleteRoomActivityController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/room-activities/{id}', name: 'api_room_activities_delete', methods: ['DELETE'])]
    #[IsGranted('room_activities.manage')]
    #[OA\Delete(
        path: '/api/room-activities/{id}',
        summary: 'Eliminar una actividad de sala',
        description: 'Requiere el permiso "room_activities.manage" (Admin y Coordinador).',
        tags: ['Actividades de sala'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Actividad de sala eliminada'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'La actividad de sala no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new DeleteRoomActivityCommand($id));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException && 'handler.room_activity.not_found' === $previous->getTranslationKey()) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.room_activity.deleted');
    }
}
