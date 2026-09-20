<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetRole;

class GetRoleQuery
{
    public function __construct(
        public readonly string $roleId
    ) {}
}
