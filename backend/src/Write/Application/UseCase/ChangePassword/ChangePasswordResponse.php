<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ChangePassword;

class ChangePasswordResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
