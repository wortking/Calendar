<?php

declare(strict_types=1);

namespace App\Write\Domain\Event;

class EmailVerificationRequestedEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $verificationToken,
        public string $locale,
        public ?string $temporaryPassword = null
    ) {}
}
