<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokePermissionFromRole;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\PermissionReadRepositoryInterface;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Write\Domain\Repository\RolePermissionWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RevokePermissionFromRoleCommandHandler
{
    public function __construct(
        private RoleReadRepositoryInterface $roleReadRepository,
        private PermissionReadRepositoryInterface $permissionReadRepository,
        private RolePermissionWriteRepositoryInterface $rolePermissionRepository
    ) {}

    public function __invoke(RevokePermissionFromRoleCommand $command): RevokePermissionFromRoleResponse
    {
        if (null === $this->roleReadRepository->findById($command->roleId)) {
            throw new TranslatableException('handler.role.not_found_by_id', ['%id%' => $command->roleId]);
        }

        if (null === $this->permissionReadRepository->findById($command->permissionId)) {
            throw new TranslatableException('handler.permission.not_found', ['%id%' => $command->permissionId]);
        }

        $this->rolePermissionRepository->revokePermission($command->roleId, $command->permissionId);

        return new RevokePermissionFromRoleResponse(true);
    }
}
