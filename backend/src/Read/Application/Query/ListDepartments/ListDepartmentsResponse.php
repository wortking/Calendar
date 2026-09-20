<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListDepartments;

class ListDepartmentsResponse
{
    /**
     * @param DepartmentView[] $departments
     */
    public function __construct(
        public readonly array $departments
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (DepartmentView $department) => $department->serialize(), $this->departments);
    }
}
