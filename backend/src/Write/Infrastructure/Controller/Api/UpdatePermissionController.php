<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\UpdatePermission\UpdatePermissionCommand;
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

#[OA\Tag(name: 'Administración')]
class UpdatePermissionController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/permissions/{id}', name: 'api_permissions_update', methods: ['PATCH'])]
    #[IsGranted('permissions.manage')]
    #[OA\Patch(
        path: '/api/permissions/{id}',
        summary: 'Editar la descripción de un permiso',
        description: 'Requiere el permiso "permissions.manage". El nombre del permiso no es editable.',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permiso actualizado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El permiso no existe'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdatePermissionCommand($id, isset($data['description']) ? (string) $data['description'] : null);

        try {
            $envelope = $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.permission.updated');
    }
}
