<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\UpdateCompany\UpdateCompanyCommand;
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

#[OA\Tag(name: 'Empresas')]
class UpdateCompanyController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/companies/{id}', name: 'api_companies_update', methods: ['PATCH'])]
    #[IsGranted('companies.manage')]
    #[OA\Patch(
        path: '/api/companies/{id}',
        summary: 'Actualizar una empresa',
        description: 'Requiere el permiso "companies.manage".',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'openingTime', 'closingTime'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Piscina Norte'),
                    new OA\Property(property: 'openingTime', type: 'string', example: '07:00'),
                    new OA\Property(property: 'closingTime', type: 'string', example: '22:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Empresa actualizada'),
            new OA\Response(response: 400, description: 'Error de validación'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'La empresa no existe'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdateCompanyCommand(
            $id,
            (string) ($data['name'] ?? ''),
            (string) ($data['openingTime'] ?? ''),
            (string) ($data['closingTime'] ?? '')
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
            if ($previous instanceof TranslatableException && 'handler.company.not_found' === $previous->getTranslationKey()) {
                return $this->apiResponse->error($previous, Response::HTTP_NOT_FOUND);
            }

            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.company.updated');
    }
}
