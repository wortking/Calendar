<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

interface RoleAssignmentReadRepositoryInterface
{
    /**
     * @return string[] Nombres de rol (p.ej. "ROLE_ADMIN") asignados al usuario
     */
    public function findRoleNamesByUserId(string $userId): array;

    /**
     * @return string[] Nombres de permiso (p.ej. "users.edit") que otorgan los roles del usuario
     */
    public function findPermissionNamesByUserId(string $userId): array;

    public function userHasRole(string $userId, string $roleId): bool;

    /**
     * @return string[] Ids de los permisos asignados a ese rol
     */
    public function findPermissionIdsByRoleId(string $roleId): array;
}
