<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeleteDepartment\DeleteDepartmentCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Departamentos')]
class DeleteDepartmentController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/departments/{id}', name: 'api_departments_delete', methods: ['DELETE'])]
    #[IsGranted('departments.manage')]
    #[OA\Delete(
        path: '/api/departments/{id}',
        summary: 'Eliminar un departamento',
        description: 'Requiere el permiso "departments.manage". Borra en cascada sus tipos de actividad. Falla si tiene usuarios asignados.',
        tags: ['Departamentos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Departamento eliminado'),
            new OA\Response(response: 400, description: 'Tiene usuarios asignados'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El departamento no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new DeleteDepartmentCommand($id));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException) {
                $status = 'handler.department.not_found' === $previous->getTranslationKey()
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_BAD_REQUEST;

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.department.deleted');
    }
}
