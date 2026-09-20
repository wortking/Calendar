<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\VerifyEmail;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyEmailCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.token.not_blank')]
        public string $token
    ) {}
}
