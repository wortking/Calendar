<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateRole;

class UpdateRoleCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $description
    ) {}
}
