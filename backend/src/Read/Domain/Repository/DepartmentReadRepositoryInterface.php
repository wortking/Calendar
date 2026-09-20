<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Department;

interface DepartmentReadRepositoryInterface
{
    public function findByName(string $name): ?Department;

    public function findById(string $id): ?Department;

    /**
     * @return Department[]
     */
    public function findAll(): array;

    /**
     * @return Department[]
     */
    public function findByCompanyId(string $companyId): array;
}
