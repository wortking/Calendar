<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RevokeDepartmentFromUser;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\DepartmentAssignmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RevokeDepartmentFromUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private DepartmentReadRepositoryInterface $departmentRepository,
        private DepartmentAssignmentWriteRepositoryInterface $departmentAssignmentRepository,
        private DepartmentScopeGuard $scopeGuard
    ) {}

    public function __invoke(RevokeDepartmentFromUserCommand $command): RevokeDepartmentFromUserResponse
    {
        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        if (null === $this->departmentRepository->findById($command->departmentId)) {
            throw new TranslatableException('handler.department.not_found', ['%id%' => $command->departmentId]);
        }

        $this->scopeGuard->assertOwnsDepartment($command->actingUserId, $command->departmentId);

        $this->departmentAssignmentRepository->revokeDepartment($command->userId, $command->departmentId);

        return new RevokeDepartmentFromUserResponse($command->userId, $command->departmentId);
    }
}
