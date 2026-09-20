<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResetPassword;

class ResetPasswordResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
