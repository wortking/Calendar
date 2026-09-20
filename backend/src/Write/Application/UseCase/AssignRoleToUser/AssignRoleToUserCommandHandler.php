<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignRoleToUser;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Cache\CacheKeys;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\RoleAssignmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
class AssignRoleToUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private RoleReadRepositoryInterface $roleRepository,
        private RoleAssignmentWriteRepositoryInterface $roleAssignmentRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CacheInterface $cache
    ) {}

    public function __invoke(AssignRoleToUserCommand $command): AssignRoleToUserResponse
    {
        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        $role = $this->roleRepository->findByName($command->roleName);

        if (null === $role) {
            throw new TranslatableException('handler.role.not_found', ['%name%' => $command->roleName]);
        }

        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->userId);
        $this->scopeGuard->assertCanAssignRole($command->actingUserId, $command->roleName);

        $this->roleAssignmentRepository->assignRole($command->userId, $role->getId());

        $this->cache->delete(CacheKeys::userRoles($command->userId));

        return new AssignRoleToUserResponse($command->userId, $role->getName());
    }
}
