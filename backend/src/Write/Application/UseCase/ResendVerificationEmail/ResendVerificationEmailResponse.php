<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResendVerificationEmail;

class ResendVerificationEmailResponse
{
    public function __construct(
        public readonly bool $alreadyVerified
    ) {}
}
