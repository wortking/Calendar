<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\CreateCompany\CreateCompanyCommand;
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
class CreateCompanyController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/companies', name: 'api_companies_create', methods: ['POST'])]
    #[IsGranted('companies.manage')]
    #[OA\Post(
        path: '/api/companies',
        summary: 'Crear una empresa',
        description: 'Requiere el permiso "companies.manage". openingTime/closingTime en formato "HH:MM".',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
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
            new OA\Response(response: 201, description: 'Empresa creada'),
            new OA\Response(response: 400, description: 'Error de validación o nombre ya usado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $command = new CreateCompanyCommand(
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
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.company.created', Response::HTTP_CREATED);
    }
}
