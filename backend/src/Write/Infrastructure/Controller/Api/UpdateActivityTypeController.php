<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\UpdateActivityType\UpdateActivityTypeCommand;
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

#[OA\Tag(name: 'Actividades')]
class UpdateActivityTypeController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/activity-types/{id}', name: 'api_activity_types_update', methods: ['PATCH'])]
    #[IsGranted('activity_types.manage')]
    #[OA\Patch(
        path: '/api/activity-types/{id}',
        summary: 'Actualizar un tipo de actividad',
        description: 'Requiere el permiso "activity_types.manage".',
        tags: ['Actividades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Body Pump'),
                    new OA\Property(property: 'color', type: 'string', nullable: true, example: '#ff5722'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tipo de actividad actualizado'),
            new OA\Response(response: 400, description: 'Error de validación o nombre ya usado en ese departamento'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El tipo de actividad no existe'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdateActivityTypeCommand(
            $id,
            (string) ($data['name'] ?? ''),
            isset($data['color']) && '' !== $data['color'] ? (string) $data['color'] : null
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
                $status = 'handler.activity_type.not_found' === $previous->getTranslationKey()
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_BAD_REQUEST;

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.activity_type.updated');
    }
}
