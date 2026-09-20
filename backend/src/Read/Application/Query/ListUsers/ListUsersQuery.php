<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUsers;

class ListUsersQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?string $email = null,
        public readonly ?string $departmentId = null
    ) {}
}
