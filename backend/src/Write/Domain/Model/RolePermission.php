<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use Symfony\Component\Uid\Uuid;

/**
 * Una fila de la tabla de unión role_permissions. Sin relación ORM formal
 * hacia Role/Permission, mismo motivo que UserRole.
 */
class RolePermission
{
    private Uuid $roleId;
    private Uuid $permissionId;

    public function __construct(string $roleId, string $permissionId)
    {
        $this->roleId = Uuid::fromString($roleId);
        $this->permissionId = Uuid::fromString($permissionId);
    }

    public function getRoleId(): string
    {
        return $this->roleId->toRfc4122();
    }

    public function getPermissionId(): string
    {
        return $this->permissionId->toRfc4122();
    }
}
