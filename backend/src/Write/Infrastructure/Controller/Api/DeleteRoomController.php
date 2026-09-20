<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeleteRoom\DeleteRoomCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Salas')]
class DeleteRoomController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/rooms/{id}', name: 'api_rooms_delete', methods: ['DELETE'])]
    #[IsGranted('rooms.manage')]
    #[OA\Delete(
        path: '/api/rooms/{id}',
        summary: 'Eliminar una sala',
        description: 'Requiere el permiso "rooms.manage". Se borran en cascada las actividades agendadas en esa sala.',
        tags: ['Salas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sala eliminada'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'La sala no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new DeleteRoomCommand($id));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException && 'handler.room.not_found' === $previous->getTranslationKey()) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.room.deleted');
    }
}
