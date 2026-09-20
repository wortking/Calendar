<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Domain\Model\User;
use App\Write\Application\UseCase\UpdateProfile\UpdateProfileCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Usuarios')]
class UpdateProfileController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/me', name: 'api_me_update_profile', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/me',
        summary: 'Actualizar los datos del usuario autenticado',
        description: 'El email no se puede modificar desde acá. Los campos omitidos o vacíos quedan en null.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', nullable: true, example: 'Ada'),
                    new OA\Property(property: 'lastName', type: 'string', nullable: true, example: 'Lovelace'),
                    new OA\Property(property: 'dni', type: 'string', nullable: true, example: '12345678A'),
                    new OA\Property(property: 'sex', type: 'string', nullable: true, enum: ['male', 'female', 'other']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Perfil actualizado'),
            new OA\Response(response: 400, description: 'Error de validación'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new UpdateProfileCommand(
            $currentUser->getId(),
            $this->nullableString($data, 'firstName'),
            $this->nullableString($data, 'lastName'),
            $this->nullableString($data, 'dni'),
            $this->nullableString($data, 'sex')
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
            $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        return $this->apiResponse->success(null, 'controller.profile.updated');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null === $value || '' === $value) {
            return null;
        }

        return (string) $value;
    }
}
