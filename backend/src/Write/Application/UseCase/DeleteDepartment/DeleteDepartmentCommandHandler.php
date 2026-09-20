<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteDepartment;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Write\Domain\Repository\ActivityTypeWriteRepositoryInterface;
use App\Write\Domain\Repository\DepartmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Borrar un departamento borra en cascada sus tipos de actividad. No se
 * permite si tiene usuarios asignados (misma aproximación que al borrar una
 * empresa: todavía no existe un concepto de usuario "activo/inactivo").
 */
#[AsMessageHandler]
class DeleteDepartmentCommandHandler
{
    public function __construct(
        private DepartmentWriteRepositoryInterface $departmentWriteRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeReadRepository,
        private ActivityTypeWriteRepositoryInterface $activityTypeWriteRepository
    ) {}

    public function __invoke(DeleteDepartmentCommand $command): DeleteDepartmentResponse
    {
        $department = $this->departmentWriteRepository->findById($command->id);

        if (null === $department) {
            throw new TranslatableException('handler.department.not_found', ['%id%' => $command->id]);
        }

        if ([] !== $this->departmentAssignmentRepository->findUserIdsByDepartmentIds([$command->id])) {
            throw new TranslatableException('handler.department.has_assigned_users');
        }

        foreach ($this->activityTypeReadRepository->findByDepartmentId($command->id) as $activityType) {
            $writeActivityType = $this->activityTypeWriteRepository->findById($activityType->getId());

            if (null !== $writeActivityType) {
                $this->activityTypeWriteRepository->delete($writeActivityType);
            }
        }

        $this->departmentWriteRepository->delete($department);

        return new DeleteDepartmentResponse($command->id);
    }
}
