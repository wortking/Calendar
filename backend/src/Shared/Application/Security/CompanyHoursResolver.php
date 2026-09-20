<?php

declare(strict_types=1);

namespace App\Shared\Application\Security;

use App\Read\Domain\Model\Company;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;

/**
 * Resuelve la empresa (si tiene) de un usuario, para acotar su calendario y
 * validar que sus turnos no se salgan del horario de apertura/cierre. Un
 * usuario solo puede pertenecer a departamentos de una única empresa
 * (invariante validada al asignar un departamento), así que alcanza con
 * resolver la del primer departamento que tenga una.
 */
class CompanyHoursResolver
{
    public function __construct(
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private CompanyReadRepositoryInterface $companyRepository
    ) {}

    public function resolveForUser(string $userId): ?Company
    {
        foreach ($this->departmentAssignmentRepository->findDepartmentsByUserId($userId) as $department) {
            if (null !== $department->getCompanyId()) {
                return $this->companyRepository->findById($department->getCompanyId());
            }
        }

        return null;
    }
}
