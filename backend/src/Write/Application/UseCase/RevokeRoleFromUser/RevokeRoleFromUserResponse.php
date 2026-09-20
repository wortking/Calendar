<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeRoleFromUser;

class RevokeRoleFromUserResponse
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roleId
    ) {}

    public function serialize(): array
    {
        return [
            'userId' => $this->userId,
            'roleId' => $this->roleId,
        ];
    }
}
