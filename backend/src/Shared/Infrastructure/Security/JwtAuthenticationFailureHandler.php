<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Sustituye al failure handler por defecto de Lexik para que un login
 * fallido responda con el mismo sobre {success, data, message}.
 */
class JwtAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private ApiResponse $apiResponse
    ) {}

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $minutes = (int) ($exception->getMessageData()['%minutes%'] ?? 15);

            $response = $this->apiResponse->error('security.login.too_many_attempts', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) ($minutes * 60));

            return $response;
        }

        if ($exception instanceof CustomUserMessageAccountStatusException) {
            return $this->apiResponse->error($exception->getMessageKey(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->apiResponse->error('security.login.invalid_credentials', Response::HTTP_UNAUTHORIZED);
    }
}
