<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\ActivityType;

interface ActivityTypeReadRepositoryInterface
{
    public function findById(string $id): ?ActivityType;

    public function findByDepartmentIdAndName(string $departmentId, string $name): ?ActivityType;

    /**
     * @return ActivityType[]
     */
    public function findByDepartmentId(string $departmentId): array;

    /**
     * @param string[] $departmentIds
     * @return ActivityType[]
     */
    public function findByDepartmentIds(array $departmentIds): array;

    /**
     * @return ActivityType[]
     */
    public function findAll(): array;
}
