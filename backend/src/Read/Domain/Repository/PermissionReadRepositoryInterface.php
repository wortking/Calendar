<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Permission;

interface PermissionReadRepositoryInterface
{
    public function findById(string $id): ?Permission;

    public function findByName(string $name): ?Permission;

    /**
     * @return Permission[]
     */
    public function findAll(): array;
}
