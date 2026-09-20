<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\DeleteCompany\DeleteCompanyCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Empresas')]
class DeleteCompanyController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/companies/{id}', name: 'api_companies_delete', methods: ['DELETE'])]
    #[IsGranted('companies.manage')]
    #[OA\Delete(
        path: '/api/companies/{id}',
        summary: 'Eliminar una empresa',
        description: 'Requiere el permiso "companies.manage". Borra en cascada sus departamentos y los tipos de actividad de esos departamentos. Falla si algún departamento tiene usuarios asignados.',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Empresa eliminada'),
            new OA\Response(response: 400, description: 'Tiene usuarios asignados a alguno de sus departamentos'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'La empresa no existe'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new DeleteCompanyCommand($id));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException) {
                $status = 'handler.company.not_found' === $previous->getTranslationKey()
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_BAD_REQUEST;

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.company.deleted');
    }
}
