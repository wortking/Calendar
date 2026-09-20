<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoles;

use App\Read\Domain\Repository\RoleReadRepositoryInterface;

class ListRolesQueryHandler
{
    public function __construct(
        private RoleReadRepositoryInterface $roleRepository
    ) {}

    public function __invoke(ListRolesQuery $query): ListRolesResponse
    {
        $roles = array_map(
            static fn ($role) => new RoleView($role->getId(), $role->getName(), $role->getDescription()),
            $this->roleRepository->findAll()
        );

        return new ListRolesResponse($roles);
    }
}
