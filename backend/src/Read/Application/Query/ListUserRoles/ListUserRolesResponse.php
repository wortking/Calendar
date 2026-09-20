<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUserRoles;

class ListUserRolesResponse
{
    /**
     * @param string[] $roles
     * @param string[] $permissions
     */
    public function __construct(
        public readonly array $roles,
        public readonly array $permissions
    ) {}

    public function serialize(): array
    {
        return [
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }
}
