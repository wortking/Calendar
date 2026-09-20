<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeRoleFromUser;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\RoleAssignmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RevokeRoleFromUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private RoleReadRepositoryInterface $roleRepository,
        private RoleAssignmentWriteRepositoryInterface $roleAssignmentRepository,
        private DepartmentScopeGuard $scopeGuard
    ) {}

    public function __invoke(RevokeRoleFromUserCommand $command): RevokeRoleFromUserResponse
    {
        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        $role = $this->roleRepository->findById($command->roleId);

        if (null === $role) {
            throw new TranslatableException('handler.role.not_found_by_id', ['%id%' => $command->roleId]);
        }

        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->userId);
        $this->scopeGuard->assertCanAssignRole($command->actingUserId, $role->getName());

        $this->roleAssignmentRepository->revokeRole($command->userId, $command->roleId);

        return new RevokeRoleFromUserResponse($command->userId, $command->roleId);
    }
}
