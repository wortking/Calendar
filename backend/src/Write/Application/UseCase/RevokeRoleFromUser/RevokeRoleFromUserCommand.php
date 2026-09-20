<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeRoleFromUser;

class RevokeRoleFromUserCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roleId,
        public readonly string $actingUserId
    ) {}
}
