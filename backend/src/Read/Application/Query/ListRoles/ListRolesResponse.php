<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoles;

class ListRolesResponse
{
    /**
     * @param RoleView[] $roles
     */
    public function __construct(
        public readonly array $roles
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (RoleView $role) => $role->serialize(), $this->roles);
    }
}
