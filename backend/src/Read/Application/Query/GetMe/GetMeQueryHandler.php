<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetMe;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserImageReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;

class GetMeQueryHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private UserImageReadRepositoryInterface $userImageRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private CompanyReadRepositoryInterface $companyRepository
    ) {}

    public function __invoke(GetMeQuery $query): GetMeResponse
    {
        $user = $this->userRepository->findById($query->userId);

        if (null === $user) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $query->userId]);
        }

        $activeImage = array_values(array_filter(
            $this->userImageRepository->findByUserId($query->userId),
            static fn ($image) => $image->isActive()
        ))[0] ?? null;

        $userDepartments = $this->departmentAssignmentRepository->findDepartmentsByUserId($query->userId);

        $departments = array_map(
            static fn ($department) => ['id' => $department->getId(), 'name' => $department->getName()],
            $userDepartments
        );

        // Un usuario solo puede pertenecer a departamentos de una única
        // empresa (invariante validada al asignar), así que alcanza con
        // resolver la del primer departamento que tenga una.
        $company = null;
        foreach ($userDepartments as $department) {
            if (null !== $department->getCompanyId()) {
                $company = $this->companyRepository->findById($department->getCompanyId());
                break;
            }
        }

        return new GetMeResponse(
            $user->getId(),
            $user->getEmail(),
            $user->getRoles(),
            $user->getDni(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getSex(),
            $user->getLastLoginAt(),
            $user->getEmailVerifiedAt(),
            $activeImage?->getUrl(),
            $departments,
            null !== $company
                ? ['id' => $company->getId(), 'name' => $company->getName(), 'openingTime' => $company->getOpeningTime()->format('H:i'), 'closingTime' => $company->getClosingTime()->format('H:i')]
                : null,
            $user->getMustChangePassword()
        );
    }
}
