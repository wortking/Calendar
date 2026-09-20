<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

interface DepartmentAssignmentWriteRepositoryInterface
{
    public function assignDepartment(string $userId, string $departmentId): void;

    public function revokeDepartment(string $userId, string $departmentId): void;
}
