<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Cache\CacheKeys;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lista de JWT invalidados manualmente (logout) antes de su expiración
 * natural. Un JWT es stateless por diseño, así que la única forma de
 * revocarlo antes de tiempo es recordar su "jti" hasta que hubiera
 * expirado igualmente (TTL = tiempo restante del token en el momento
 * del logout, nunca más).
 */
final class JwtBlocklist
{
    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache
    ) {}

    public function block(string $jti, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        $item = $this->cache->getItem(CacheKeys::jwtBlocklist($jti));
        $item->set(true);
        $item->expiresAfter($ttlSeconds);
        $this->cache->save($item);
    }

    public function isBlocked(string $jti): bool
    {
        return $this->cache->getItem(CacheKeys::jwtBlocklist($jti))->isHit();
    }
}
