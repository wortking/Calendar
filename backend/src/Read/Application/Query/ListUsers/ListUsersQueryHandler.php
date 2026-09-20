<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUsers;

use App\Shared\Application\Security\CompanyHoursResolver;
use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;

class ListUsersQueryHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CompanyHoursResolver $companyHoursResolver
    ) {}

    public function __invoke(ListUsersQuery $query): ListUsersResponse
    {
        $page = max(1, $query->page);
        $limit = min(100, max(1, $query->limit));

        $ids = $this->scopeGuard->scopedUserIdsFor($query->actingUserId);

        if (null !== $query->departmentId) {
            $departmentUserIds = $this->departmentAssignmentRepository->findUserIdsByDepartmentIds([$query->departmentId]);
            $ids = null === $ids ? $departmentUserIds : array_values(array_intersect($ids, $departmentUserIds));
        }

        $userEntities = $this->userRepository->findPageFiltered($ids, $query->email, $page, $limit);
        $total = $this->userRepository->countFiltered($ids, $query->email);

        $users = array_map(
            function ($user) {
                $company = $this->companyHoursResolver->resolveForUser($user->getId());

                return new UserSummaryView(
                    $user->getId(),
                    $user->getEmail(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getRoles(),
                    array_map(
                        static fn ($department) => ['id' => $department->getId(), 'name' => $department->getName()],
                        $this->departmentAssignmentRepository->findDepartmentsByUserId($user->getId())
                    ),
                    null !== $company ? ['id' => $company->getId(), 'name' => $company->getName()] : null,
                    $user->getDeactivatedAt()?->format('Y-m-d\TH:i:s')
                );
            },
            $userEntities
        );

        return new ListUsersResponse($users, $page, $limit, $total);
    }
}
