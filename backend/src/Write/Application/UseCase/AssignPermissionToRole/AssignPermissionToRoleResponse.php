<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignPermissionToRole;

class AssignPermissionToRoleResponse
{
    public function __construct(
        public readonly string $roleId,
        public readonly string $permissionId
    ) {}

    public function serialize(): array
    {
        return [
            'roleId' => $this->roleId,
            'permissionId' => $this->permissionId,
        ];
    }
}
