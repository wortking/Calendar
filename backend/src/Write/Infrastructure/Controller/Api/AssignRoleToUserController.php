<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\AssignRoleToUser\AssignRoleToUserCommand;
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

#[OA\Tag(name: 'Usuarios')]
class AssignRoleToUserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/users/{id}/roles', name: 'api_users_assign_role', methods: ['POST'])]
    #[IsGranted('users.edit')]
    #[OA\Post(
        path: '/api/users/{id}/roles',
        summary: 'Asignar un rol a un usuario',
        description: 'Requiere el permiso "users.edit" (lo tiene por defecto ROLE_ADMIN).',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', example: 'ROLE_ADMIN'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rol asignado'),
            new OA\Response(response: 400, description: 'Error de validación'),
            new OA\Response(response: 403, description: 'No autorizado (falta el permiso users.edit)'),
            new OA\Response(response: 404, description: 'El usuario o el rol no existen'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new AssignRoleToUserCommand($id, (string) ($data['role'] ?? ''), $currentUser->getId());

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
            if ($previous instanceof TranslatableException && str_starts_with($previous->getTranslationKey(), 'handler.access_denied.')) {
                return $this->apiResponse->error($previous, Response::HTTP_FORBIDDEN);
            }

            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        $handledStamp = $envelope->last(HandledStamp::class);
        $response = $handledStamp?->getResult();

        return $this->apiResponse->success($response?->serialize(), 'controller.role.assigned');
    }
}
