<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller\Api;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Autenticación', description: 'Login y obtención de tokens JWT')]
class LoginController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login_check',
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario con email y contraseña y devuelve un token JWT para usar en el resto de endpoints (header Authorization: Bearer {token}).',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@bicycle-sale.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'changeme123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login correcto',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciales inválidas'),
        ]
    )]
    public function __invoke(): Response
    {
        // Esta acción nunca se ejecuta: el firewall "login" (json_login) intercepta
        // la petición antes de que llegue al controlador y devuelve el JWT.
        throw new \LogicException('Esta ruta es interceptada por el firewall de seguridad.');
    }
}
