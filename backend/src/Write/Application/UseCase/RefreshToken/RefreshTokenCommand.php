<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RefreshToken;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.refresh_token.not_blank')]
        public string $refreshToken
    ) {}
}
