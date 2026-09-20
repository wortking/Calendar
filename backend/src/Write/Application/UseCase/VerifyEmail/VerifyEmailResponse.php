<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\VerifyEmail;

class VerifyEmailResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
