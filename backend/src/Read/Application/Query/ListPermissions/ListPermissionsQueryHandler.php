<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListPermissions;

use App\Read\Domain\Repository\PermissionReadRepositoryInterface;

class ListPermissionsQueryHandler
{
    public function __construct(
        private PermissionReadRepositoryInterface $permissionRepository
    ) {}

    public function __invoke(ListPermissionsQuery $query): ListPermissionsResponse
    {
        $permissions = array_map(
            static fn ($permission) => new PermissionView($permission->getId(), $permission->getName(), $permission->getDescription()),
            $this->permissionRepository->findAll()
        );

        return new ListPermissionsResponse($permissions);
    }
}
