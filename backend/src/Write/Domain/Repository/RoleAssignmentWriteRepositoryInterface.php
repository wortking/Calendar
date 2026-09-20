<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

interface RoleAssignmentWriteRepositoryInterface
{
    public function assignRole(string $userId, string $roleId): void;

    public function revokeRole(string $userId, string $roleId): void;
}
