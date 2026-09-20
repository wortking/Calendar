<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdatePermission;

class UpdatePermissionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $description
    ) {}
}
