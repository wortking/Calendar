<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Permission;

interface PermissionWriteRepositoryInterface
{
    public function save(Permission $permission): void;

    public function findById(string $id): ?Permission;
}
