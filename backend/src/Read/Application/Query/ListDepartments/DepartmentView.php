<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListDepartments;

class DepartmentView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $companyId,
        public readonly ?string $companyName
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'companyId' => $this->companyId,
            'companyName' => $this->companyName,
        ];
    }
}
