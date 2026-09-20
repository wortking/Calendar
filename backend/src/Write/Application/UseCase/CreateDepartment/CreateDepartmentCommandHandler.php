<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateDepartment;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Write\Domain\Model\Department;
use App\Write\Domain\Repository\DepartmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateDepartmentCommandHandler
{
    public function __construct(
        private DepartmentReadRepositoryInterface $departmentReadRepository,
        private DepartmentWriteRepositoryInterface $departmentWriteRepository,
        private CompanyReadRepositoryInterface $companyReadRepository
    ) {}

    public function __invoke(CreateDepartmentCommand $command): CreateDepartmentResponse
    {
        if (null !== $this->departmentReadRepository->findByName($command->name)) {
            throw new TranslatableException('handler.department.name_taken', ['%name%' => $command->name]);
        }

        if (null === $this->companyReadRepository->findById($command->companyId)) {
            throw new TranslatableException('handler.company.not_found', ['%id%' => $command->companyId]);
        }

        $id = Uuid::v7()->toRfc4122();
        $department = new Department($id, $command->name, $command->description, $command->companyId);

        $this->departmentWriteRepository->save($department);

        return new CreateDepartmentResponse($department->getId(), $department->getName(), $department->getDescription(), $department->getCompanyId());
    }
}
