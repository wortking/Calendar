<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\Logout;

class LogoutCommand
{
    public function __construct(
        public readonly string $jti,
        public readonly int $accessTokenTtlSeconds,
        public readonly ?string $refreshToken = null
    ) {}
}
