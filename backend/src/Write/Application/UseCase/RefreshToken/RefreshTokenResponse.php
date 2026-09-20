<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RefreshToken;

class RefreshTokenResponse
{
    public function __construct(
        public readonly string $token,
        public readonly string $refreshToken
    ) {}

    public function serialize(): array
    {
        return [
            'token' => $this->token,
            'refreshToken' => $this->refreshToken,
        ];
    }
}
