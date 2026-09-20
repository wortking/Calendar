<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokePermissionFromRole;

class RevokePermissionFromRoleCommand
{
    public function __construct(
        public readonly string $roleId,
        public readonly string $permissionId
    ) {}
}
