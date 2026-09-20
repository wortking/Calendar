<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Department;

interface DepartmentWriteRepositoryInterface
{
    public function save(Department $department): void;

    public function findById(string $id): ?Department;

    public function delete(Department $department): void;
}
