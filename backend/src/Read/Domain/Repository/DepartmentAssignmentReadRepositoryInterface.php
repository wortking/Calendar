<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Department;

interface DepartmentAssignmentReadRepositoryInterface
{
    /**
     * @return Department[]
     */
    public function findDepartmentsByUserId(string $userId): array;

    /**
     * Ids de usuario asignados a cualquiera de los departamentos dados (sin duplicados).
     *
     * @param string[] $departmentIds
     * @return string[]
     */
    public function findUserIdsByDepartmentIds(array $departmentIds): array;
}
