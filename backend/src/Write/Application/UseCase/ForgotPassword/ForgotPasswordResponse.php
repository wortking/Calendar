<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ForgotPassword;

class ForgotPasswordResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
