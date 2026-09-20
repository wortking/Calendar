<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeDepartmentFromUser;

class RevokeDepartmentFromUserCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $departmentId,
        public readonly string $actingUserId
    ) {}
}
