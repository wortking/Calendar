<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\Logout;

class LogoutResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
