<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Domain\Model\RefreshToken;
use App\Write\Domain\Repository\RefreshTokenWriteRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Sustituye al success handler por defecto de Lexik para que el login
 * responda con el mismo sobre {success, data, message} que el resto de la API.
 */
class JwtAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EventDispatcherInterface $dispatcher,
        private ApiResponse $apiResponse,
        private RefreshTokenWriteRepositoryInterface $refreshTokenRepository
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);
        $refreshToken = $this->issueRefreshToken($user);

        $data = ['token' => $jwt, 'refreshToken' => $refreshToken];
        $response = $this->apiResponse->success($data, 'security.login.success');

        // Se sigue disparando el evento estándar de Lexik para que
        // LoginSuccessListener (registro de última conexión) siga funcionando.
        $event = new AuthenticationSuccessEvent($data, $user, $response);
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        return $response;
    }

    private function issueRefreshToken(UserInterface $user): string
    {
        /** @var \App\Read\Domain\Model\User $user */
        $rawToken = bin2hex(random_bytes(32));

        $token = new RefreshToken(
            Uuid::v7()->toRfc4122(),
            $user->getId(),
            hash('sha256', $rawToken),
            new \DateTimeImmutable(sprintf('+%d days', RefreshToken::TTL_DAYS))
        );

        $this->refreshTokenRepository->save($token);

        return $rawToken;
    }
}
