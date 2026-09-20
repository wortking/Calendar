<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignDepartmentToUser;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\DepartmentAssignmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AssignDepartmentToUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private DepartmentReadRepositoryInterface $departmentRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentReadRepository,
        private DepartmentAssignmentWriteRepositoryInterface $departmentAssignmentRepository,
        private DepartmentScopeGuard $scopeGuard
    ) {}

    public function __invoke(AssignDepartmentToUserCommand $command): AssignDepartmentToUserResponse
    {
        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        $department = $this->departmentRepository->findById($command->departmentId);

        if (null === $department) {
            throw new TranslatableException('handler.department.not_found', ['%id%' => $command->departmentId]);
        }

        $this->scopeGuard->assertOwnsDepartment($command->actingUserId, $command->departmentId);

        // Un usuario solo puede pertenecer a departamentos de una única
        // empresa: si el departamento a sumar tiene empresa y el usuario ya
        // tiene departamentos de OTRA empresa, se rechaza.
        if (null !== $department->getCompanyId()) {
            foreach ($this->departmentAssignmentReadRepository->findDepartmentsByUserId($command->userId) as $existingDepartment) {
                if (null !== $existingDepartment->getCompanyId() && $existingDepartment->getCompanyId() !== $department->getCompanyId()) {
                    throw new TranslatableException('handler.department.company_conflict');
                }
            }
        }

        $this->departmentAssignmentRepository->assignDepartment($command->userId, $department->getId());

        return new AssignDepartmentToUserResponse($command->userId, $department->getId(), $department->getName());
    }
}
