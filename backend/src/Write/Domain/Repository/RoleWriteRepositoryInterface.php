<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Role;

interface RoleWriteRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(string $id): ?Role;
}
