<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeDepartmentFromUser;

class RevokeDepartmentFromUserResponse
{
    public function __construct(
        public readonly string $userId,
        public readonly string $departmentId
    ) {}

    public function serialize(): array
    {
        return [
            'userId' => $this->userId,
            'departmentId' => $this->departmentId,
        ];
    }
}
