<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateProfile;

class UpdateProfileResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
