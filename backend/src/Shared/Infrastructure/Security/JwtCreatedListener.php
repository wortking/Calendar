<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

/**
 * Añade un identificador único (jti) a cada JWT emitido, necesario para
 * poder revocarlo individualmente en el logout (ver JwtBlocklist).
 */
#[AsEventListener(event: Events::JWT_CREATED)]
class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $data = $event->getData();
        $data['jti'] = Uuid::v4()->toRfc4122();
        $event->setData($data);
    }
}
