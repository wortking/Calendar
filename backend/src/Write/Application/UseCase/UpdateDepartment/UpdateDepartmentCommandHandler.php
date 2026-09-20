<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateDepartment;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Write\Domain\Repository\DepartmentWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDepartmentCommandHandler
{
    public function __construct(
        private DepartmentReadRepositoryInterface $departmentReadRepository,
        private DepartmentWriteRepositoryInterface $departmentWriteRepository,
        private CompanyReadRepositoryInterface $companyReadRepository
    ) {}

    public function __invoke(UpdateDepartmentCommand $command): UpdateDepartmentResponse
    {
        $department = $this->departmentWriteRepository->findById($command->id);

        if (null === $department) {
            throw new TranslatableException('handler.department.not_found', ['%id%' => $command->id]);
        }

        if ($command->name !== $department->getName()) {
            $existing = $this->departmentReadRepository->findByName($command->name);
            if (null !== $existing && $existing->getId() !== $department->getId()) {
                throw new TranslatableException('handler.department.name_taken', ['%name%' => $command->name]);
            }

            $department->rename($command->name);
        }

        if (null === $this->companyReadRepository->findById($command->companyId)) {
            throw new TranslatableException('handler.company.not_found', ['%id%' => $command->companyId]);
        }

        $department->setDescription($command->description);
        $department->changeCompany($command->companyId);
        $this->departmentWriteRepository->save($department);

        return new UpdateDepartmentResponse($department->getId(), $department->getName(), $department->getDescription(), $department->getCompanyId());
    }
}
