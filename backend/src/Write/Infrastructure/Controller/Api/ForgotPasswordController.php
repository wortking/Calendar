<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\ForgotPassword\ForgotPasswordCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Autenticación')]
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $forgotPasswordLimiter
    ) {}

    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    #[OA\Post(
        path: '/api/forgot-password',
        summary: 'Solicitar código para restablecer la contraseña',
        description: 'Envía un código de 5 dígitos por email, válido 15 minutos. La respuesta es siempre la misma exista o no el email, para no revelar qué cuentas existen.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@bicycle-sale.test'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Si el email existe, se ha enviado un código'),
            new OA\Response(response: 400, description: 'Error de validación'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = $this->forgotPasswordLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new ForgotPasswordCommand((string) ($data['email'] ?? ''));

        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        $this->messageBus->dispatch($command);

        return $this->apiResponse->success(null, 'controller.forgot_password.sent');
    }
}
