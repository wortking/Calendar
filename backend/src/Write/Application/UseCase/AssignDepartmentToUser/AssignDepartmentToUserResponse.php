<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignDepartmentToUser;

class AssignDepartmentToUserResponse
{
    public function __construct(
        public readonly string $userId,
        public readonly string $departmentId,
        public readonly string $departmentName
    ) {}

    public function serialize(): array
    {
        return [
            'userId' => $this->userId,
            'departmentId' => $this->departmentId,
            'departmentName' => $this->departmentName,
        ];
    }
}
