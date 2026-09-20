<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteDepartment;

class DeleteDepartmentResponse
{
    public function __construct(
        public readonly string $id
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
