<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateDepartment;

class CreateDepartmentResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $companyId
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'companyId' => $this->companyId,
        ];
    }
}
