<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class RolePermission
{
    private Uuid $roleId;
    private Uuid $permissionId;

    public function getRoleId(): string
    {
        return $this->roleId->toRfc4122();
    }

    public function getPermissionId(): string
    {
        return $this->permissionId->toRfc4122();
    }
}
