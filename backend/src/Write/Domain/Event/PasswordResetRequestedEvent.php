<?php

declare(strict_types=1);

namespace App\Write\Domain\Event;

class PasswordResetRequestedEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $code,
        public string $locale
    ) {}
}
