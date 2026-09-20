<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetRole;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoleAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;

class GetRoleQueryHandler
{
    public function __construct(
        private RoleReadRepositoryInterface $roleRepository,
        private RoleAssignmentReadRepositoryInterface $roleAssignmentRepository
    ) {}

    public function __invoke(GetRoleQuery $query): GetRoleResponse
    {
        $role = $this->roleRepository->findById($query->roleId);

        if (null === $role) {
            throw new TranslatableException('handler.role.not_found_by_id', ['%id%' => $query->roleId]);
        }

        $permissionIds = $this->roleAssignmentRepository->findPermissionIdsByRoleId($role->getId());

        return new GetRoleResponse($role->getId(), $role->getName(), $role->getDescription(), $permissionIds);
    }
}
