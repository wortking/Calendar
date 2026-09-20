<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignRoleToUser;

class AssignRoleToUserResponse
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roleName
    ) {}

    public function serialize(): array
    {
        return [
            'userId' => $this->userId,
            'roleName' => $this->roleName,
        ];
    }
}
