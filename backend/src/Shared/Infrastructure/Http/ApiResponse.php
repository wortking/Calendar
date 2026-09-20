<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiResponse
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    /**
     * @param array<string, string|int> $params
     */
    public function success(mixed $data = null, string $messageKey = 'listener.success.ok', int $status = Response::HTTP_OK, array $params = []): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'message' => $this->translator->trans($messageKey, $params),
        ], $status);
    }

    /**
     * Acepta una clave de traducción, un texto ya traducido (p.ej. de Symfony
     * Validator, que ya traduce sus propios mensajes), o directamente la
     * excepción capturada (si es TranslatableException, se traduce su clave).
     */
    public function error(string|\Throwable $message, int $status = Response::HTTP_BAD_REQUEST, mixed $data = null): JsonResponse
    {
        $text = match (true) {
            $message instanceof TranslatableException => $this->translator->trans($message->getTranslationKey(), $message->getTranslationParams()),
            $message instanceof \Throwable => $this->translator->trans($message->getMessage()),
            default => $this->translator->trans($message),
        };

        return new JsonResponse([
            'success' => false,
            'data' => $data,
            'message' => $text,
        ], $status);
    }
}
