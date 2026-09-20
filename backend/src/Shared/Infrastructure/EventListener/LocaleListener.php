<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Negocia el idioma de la petición vía el header Accept-Language,
 * entre los idiomas soportados por la API (es, en). Por defecto: es.
 */
#[AsEventListener(event: 'kernel.request', priority: 20)]
class LocaleListener
{
    private const SUPPORTED_LOCALES = ['es', 'en'];
    private const DEFAULT_LOCALE = 'es';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $locale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES) ?? self::DEFAULT_LOCALE;

        $request->setLocale($locale);
    }
}
