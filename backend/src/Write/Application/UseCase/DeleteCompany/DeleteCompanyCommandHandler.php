<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteCompany;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Write\Domain\Repository\ActivityTypeWriteRepositoryInterface;
use App\Write\Domain\Repository\CompanyWriteRepositoryInterface;
use App\Write\Domain\Repository\DepartmentWriteRepositoryInterface;
use App\Write\Domain\Repository\RoomWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Borrar una empresa borra en cascada sus departamentos (y los tipos de
 * actividad de esos departamentos) y sus salas. No se permite si algún
 * departamento tiene usuarios asignados (todavía no existe un concepto de
 * usuario "activo/inactivo" en el sistema, así que por ahora esto se
 * aproxima con "tiene usuarios asignados, sin importar el rol").
 */
#[AsMessageHandler]
class DeleteCompanyCommandHandler
{
    public function __construct(
        private CompanyWriteRepositoryInterface $companyWriteRepository,
        private DepartmentReadRepositoryInterface $departmentReadRepository,
        private DepartmentWriteRepositoryInterface $departmentWriteRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeReadRepository,
        private ActivityTypeWriteRepositoryInterface $activityTypeWriteRepository,
        private RoomReadRepositoryInterface $roomReadRepository,
        private RoomWriteRepositoryInterface $roomWriteRepository
    ) {}

    public function __invoke(DeleteCompanyCommand $command): DeleteCompanyResponse
    {
        $company = $this->companyWriteRepository->findById($command->id);

        if (null === $company) {
            throw new TranslatableException('handler.company.not_found', ['%id%' => $command->id]);
        }

        $departmentIds = array_values(array_map(
            static fn ($department) => $department->getId(),
            array_filter(
                $this->departmentReadRepository->findAll(),
                static fn ($department) => $department->getCompanyId() === $command->id
            )
        ));

        if ([] !== $departmentIds
            && [] !== $this->departmentAssignmentRepository->findUserIdsByDepartmentIds($departmentIds)) {
            throw new TranslatableException('handler.company.has_assigned_users');
        }

        foreach ($departmentIds as $departmentId) {
            foreach ($this->activityTypeReadRepository->findByDepartmentId($departmentId) as $activityType) {
                $writeActivityType = $this->activityTypeWriteRepository->findById($activityType->getId());

                if (null !== $writeActivityType) {
                    $this->activityTypeWriteRepository->delete($writeActivityType);
                }
            }

            $writeDepartment = $this->departmentWriteRepository->findById($departmentId);

            if (null !== $writeDepartment) {
                $this->departmentWriteRepository->delete($writeDepartment);
            }
        }

        foreach ($this->roomReadRepository->findByCompanyId($command->id) as $room) {
            $writeRoom = $this->roomWriteRepository->findById($room->getId());

            if (null !== $writeRoom) {
                $this->roomWriteRepository->delete($writeRoom);
            }
        }

        $this->companyWriteRepository->delete($company);

        return new DeleteCompanyResponse($command->id);
    }
}
