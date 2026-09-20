<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Rechaza cualquier JWT cuyo "jti" esté en la lista de revocados (logout),
 * aunque su firma y expiración sigan siendo válidas.
 */
#[AsEventListener(event: Events::JWT_DECODED)]
class JwtDecodedListener
{
    public function __construct(
        private JwtBlocklist $blocklist
    ) {}

    public function __invoke(JWTDecodedEvent $event): void
    {
        $jti = $event->getPayload()['jti'] ?? null;

        if (is_string($jti) && $this->blocklist->isBlocked($jti)) {
            $event->markAsInvalid();
        }
    }
}
