<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeleteShift\DeleteShiftCommand;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Turnos')]
class DeleteShiftController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{id}/shifts/{shiftId}', name: 'api_users_delete_shift', methods: ['DELETE'])]
    #[IsGranted('shifts.manage')]
    #[OA\Delete(
        path: '/api/users/{id}/shifts/{shiftId}',
        summary: 'Quitar un turno de un usuario',
        description: 'Requiere el permiso "shifts.manage".',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'shiftId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Turno eliminado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El turno no existe para ese usuario'),
        ]
    )]
    public function __invoke(string $id, string $shiftId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->messageBus->dispatch(new DeleteShiftCommand($id, $shiftId, $currentUser->getId()));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException && str_starts_with($previous->getTranslationKey(), 'handler.access_denied.')) {
                return $this->apiResponse->error($previous, Response::HTTP_FORBIDDEN);
            }

            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.shift.deleted');
    }
}
