<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api\User;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeactivateUser\DeactivateUserCommand;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Usuarios')]
class DeactivateUserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{id}/deactivate', name: 'api_users_deactivate', methods: ['POST'])]
    #[IsGranted('users.deactivate')]
    #[OA\Post(
        path: '/api/users/{id}/deactivate',
        summary: 'Dar de baja (lógica) a un usuario',
        description: 'Requiere el permiso "users.deactivate". El usuario deja de poder iniciar sesión, se cancelan todos sus turnos pendientes (el historial ya cumplido no se toca) y las actividades de sala que tenía agendadas en esos turnos pasan a quien ejecuta la baja.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario dado de baja (ver detalle en la respuesta)'),
            new OA\Response(response: 400, description: 'No se puede dar de baja a uno mismo'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El usuario no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $command = new DeactivateUserCommand($id, $currentUser->getId());

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
                    'handler.user.not_found' === $previous->getTranslationKey() => Response::HTTP_NOT_FOUND,
                    str_starts_with($previous->getTranslationKey(), 'handler.access_denied.') => Response::HTTP_FORBIDDEN,
                    default => Response::HTTP_BAD_REQUEST,
                };

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.user.deactivated');
    }
}
