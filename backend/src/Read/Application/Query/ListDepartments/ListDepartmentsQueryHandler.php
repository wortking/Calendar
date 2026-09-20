<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListDepartments;

use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;

class ListDepartmentsQueryHandler
{
    public function __construct(
        private DepartmentReadRepositoryInterface $departmentRepository,
        private CompanyReadRepositoryInterface $companyRepository
    ) {}

    public function __invoke(ListDepartmentsQuery $query): ListDepartmentsResponse
    {
        $departments = array_map(
            function ($department) {
                $company = null !== $department->getCompanyId()
                    ? $this->companyRepository->findById($department->getCompanyId())
                    : null;

                return new DepartmentView(
                    $department->getId(),
                    $department->getName(),
                    $department->getDescription(),
                    $department->getCompanyId(),
                    $company?->getName()
                );
            },
            $this->departmentRepository->findAll()
        );

        return new ListDepartmentsResponse($departments);
    }
}
