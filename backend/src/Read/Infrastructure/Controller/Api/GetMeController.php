<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Application\Query\GetMe\GetMeQuery;
use App\Read\Application\Query\GetMe\GetMeQueryHandler;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Autenticación')]
class GetMeController extends AbstractController
{
    public function __construct(
        private GetMeQueryHandler $handler,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/me',
        summary: 'Datos del usuario autenticado',
        description: 'Devuelve el id, email y roles del usuario del token JWT enviado. Requiere autenticación (Bearer token).',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: '1e3a15873ce4a36e9da4378bdca4fce0'),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@bicycle-sale.test'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_ADMIN', 'ROLE_USER']),
                        new OA\Property(property: 'dni', type: 'string', nullable: true, example: '12345678A'),
                        new OA\Property(property: 'firstName', type: 'string', nullable: true, example: 'Ada'),
                        new OA\Property(property: 'lastName', type: 'string', nullable: true, example: 'Lovelace'),
                        new OA\Property(property: 'sex', type: 'string', nullable: true, example: 'female'),
                        new OA\Property(property: 'lastLoginAt', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'emailVerifiedAt', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'avatarUrl', type: 'string', nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $response = ($this->handler)(new GetMeQuery($currentUser->getId()));

        return $this->apiResponse->success($response->serialize());
    }
}
