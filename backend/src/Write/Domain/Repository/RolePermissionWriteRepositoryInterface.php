<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

interface RolePermissionWriteRepositoryInterface
{
    public function assignPermission(string $roleId, string $permissionId): void;

    public function revokePermission(string $roleId, string $permissionId): void;
}
