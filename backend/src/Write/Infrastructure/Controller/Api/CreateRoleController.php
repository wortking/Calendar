<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\CreateRole\CreateRoleCommand;
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

#[OA\Tag(name: 'Administración')]
class CreateRoleController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/roles', name: 'api_roles_create', methods: ['POST'])]
    #[IsGranted('roles.manage')]
    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear un rol',
        description: 'Requiere el permiso "roles.manage". El nombre debe seguir el formato ROLE_ALGO y no se puede cambiar después.',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'ROLE_MODERATOR'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rol creado'),
            new OA\Response(response: 400, description: 'Error de validación o nombre ya usado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new CreateRoleCommand(
            (string) ($data['name'] ?? ''),
            isset($data['description']) ? (string) $data['description'] : null
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
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.role.created', Response::HTTP_CREATED);
    }
}
