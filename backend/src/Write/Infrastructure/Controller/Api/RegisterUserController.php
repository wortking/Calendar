<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\RegisterUser\RegisterUserCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Usuarios')]
class RegisterUserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $registerLimiter
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[IsGranted('users.edit')]
    #[OA\Post(
        path: '/api/register',
        summary: 'Crear un usuario nuevo',
        description: 'No es autoservicio: solo un admin con el permiso "users.edit" puede crear cuentas. El usuario creado siempre recibe ROLE_USER (no se puede elegir rol desde aquí; los roles se asignan aparte). La contraseña la genera el sistema (nunca la elige quien crea la cuenta) y se le envía al usuario por email junto con el link de verificación; queda obligado a cambiarla en su primer inicio de sesión.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'nuevo@bicycle-sale.test'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario registrado'),
            new OA\Response(response: 400, description: 'Error de validación'),
            new OA\Response(response: 409, description: 'Ya existe un usuario con ese email'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = $this->registerLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new RegisterUserCommand((string) ($data['email'] ?? ''));

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
                return $this->apiResponse->error($previous, Response::HTTP_CONFLICT);
            }

            throw $e;
        }

        $handledStamp = $envelope->last(HandledStamp::class);
        $response = $handledStamp?->getResult();

        return $this->apiResponse->success($response?->serialize(), 'controller.user.registered', Response::HTTP_CREATED);
    }
}
