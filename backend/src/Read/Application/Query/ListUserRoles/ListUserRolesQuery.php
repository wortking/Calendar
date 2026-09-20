<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUserRoles;

class ListUserRolesQuery
{
    public function __construct(
        public readonly string $userId
    ) {}
}
