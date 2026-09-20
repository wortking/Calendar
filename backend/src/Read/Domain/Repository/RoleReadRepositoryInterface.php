<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Role;

interface RoleReadRepositoryInterface
{
    public function findByName(string $name): ?Role;

    public function findById(string $id): ?Role;

    /**
     * @return Role[]
     */
    public function findAll(): array;
}
