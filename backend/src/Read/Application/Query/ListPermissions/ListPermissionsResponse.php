<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListPermissions;

class ListPermissionsResponse
{
    /**
     * @param PermissionView[] $permissions
     */
    public function __construct(
        public readonly array $permissions
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (PermissionView $permission) => $permission->serialize(), $this->permissions);
    }
}
