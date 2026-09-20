<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResendVerificationEmail;

class ResendVerificationEmailCommand
{
    public function __construct(
        public string $userId
    ) {}
}
