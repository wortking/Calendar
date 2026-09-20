<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokePermissionFromRole;

class RevokePermissionFromRoleResponse
{
    public function __construct(
        public readonly bool $success
    ) {}
}
