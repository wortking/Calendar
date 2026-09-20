<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Normaliza cualquier excepción no capturada bajo /api (403, 404, 500...)
 * al mismo formato {success, data, message} del resto de la API, en vez
 * de las páginas HTML de error por defecto de Symfony.
 */
#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function __construct(
        private ApiResponse $apiResponse
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api') || str_starts_with($path, '/api/doc')) {
            return;
        }

        $exception = $event->getThrowable();

        $status = match (true) {
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            $exception instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        if ($status >= 500) {
            $event->setResponse($this->apiResponse->error('listener.error.internal', $status));

            return;
        }

        $event->setResponse($this->apiResponse->error($exception, $status));
    }
}
