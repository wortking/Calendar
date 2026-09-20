<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Red de seguridad final: cualquier respuesta bajo /api que no llegue ya en
 * el formato {success, data, message} se reenvuelve aquí. Cubre casos que no
 * pasan por nuestros controllers ni por ApiExceptionListener, como los fallos
 * de JWT del bundle Lexik (token no encontrado/inválido/caducado), que
 * construyen su propia respuesta antes de llegar a kernel.exception.
 */
#[AsEventListener(event: 'kernel.response', priority: -256)]
class ApiResponseNormalizerListener
{
    public function __construct(
        private ApiResponse $apiResponse
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // /api/doc (Swagger UI) y /api/doc.json (spec OpenAPI) deben quedar intactos.
        // Los preflight OPTIONS los responde NelmioCorsBundle antes de llegar aquí
        // y no son respuestas de negocio: envolverlos no tiene sentido.
        if (!str_starts_with($path, '/api') || str_starts_with($path, '/api/doc') || $request->isMethod('OPTIONS')) {
            return;
        }

        $response = $event->getResponse();

        // Descargas binarias (p. ej. la plantilla de importación en .xlsx) no
        // son respuestas de negocio JSON: envolverlas las rompería, y además
        // StreamedResponse::getContent() no trae el cuerpo real (se genera
        // recién al enviarse), así que ni siquiera se puede inspeccionar acá.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        $statusCode = $response->getStatusCode();

        $decoded = json_decode($response->getContent() ?: 'null', true);

        $alreadyWrapped = is_array($decoded)
            && array_key_exists('success', $decoded)
            && array_key_exists('data', $decoded)
            && array_key_exists('message', $decoded);

        if ($alreadyWrapped) {
            return;
        }

        $success = $statusCode >= 200 && $statusCode < 300;

        if ($success) {
            $newResponse = $this->apiResponse->success(is_array($decoded) ? $decoded : null, 'listener.success.ok', $statusCode);
            $this->copyPreservedHeaders($response, $newResponse);
            $event->setResponse($newResponse);

            return;
        }

        $message = match (true) {
            $statusCode >= 500 => 'listener.error.internal',
            is_array($decoded) && isset($decoded['message']) && is_string($decoded['message']) => $decoded['message'],
            default => 'listener.error.generic',
        };

        $newResponse = $this->apiResponse->error($message, $statusCode);
        $this->copyPreservedHeaders($response, $newResponse);
        $event->setResponse($newResponse);
    }

    /**
     * Al reconstruir la respuesta se pierden headers ya calculados por otros
     * listeners (CORS de NelmioCorsBundle, Retry-After del rate limiter...).
     * Se copian explícitamente en vez de todos los headers para no arrastrar
     * Content-Type/Content-Length del cuerpo original descartado.
     */
    private function copyPreservedHeaders(Response $from, Response $to): void
    {
        foreach ($from->headers->all() as $name => $values) {
            if (str_starts_with(strtolower($name), 'access-control-') || in_array(strtolower($name), ['vary', 'retry-after'], true)) {
                $to->headers->set($name, $values);
            }
        }
    }
}
