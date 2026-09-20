<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Excepción que transporta una clave de traducción (y sus parámetros) en vez
 * de un mensaje ya traducido. Así el dominio y los handlers pueden lanzar
 * errores semánticos sin depender de Symfony\Contracts\Translation\TranslatorInterface;
 * la traducción real ocurre en el borde (controllers/listeners), donde sí hay Translator.
 */
class TranslatableException extends \InvalidArgumentException
{
    /**
     * @param array<string, string|int> $translationParams
     */
    public function __construct(
        private readonly string $translationKey,
        private readonly array $translationParams = []
    ) {
        parent::__construct($translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * @return array<string, string|int>
     */
    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }
}
