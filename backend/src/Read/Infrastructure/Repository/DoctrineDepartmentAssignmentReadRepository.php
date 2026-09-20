<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Department;
use App\Read\Domain\Model\UserDepartment;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * user_departments tiene su propia entidad (UserDepartment), pero sin relación
 * ORM formal hacia User/Department (mismo criterio que UserRole). El "join" se
 * hace con varias raíces en la misma QueryBuilder, igual que
 * DoctrineRoleAssignmentReadRepository.
 */
class DoctrineDepartmentAssignmentReadRepository implements DepartmentAssignmentReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Department[]
     */
    public function findDepartmentsByUserId(string $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('d')
            ->from(Department::class, 'd')
            ->from(UserDepartment::class, 'ud')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('ud.departmentId', 'd.id'),
                $qb->expr()->eq('ud.userId', ':userId')
            ))
            ->setParameter('userId', $userId)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $departmentIds
     * @return string[]
     */
    public function findUserIdsByDepartmentIds(array $departmentIds): array
    {
        if ([] === $departmentIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();

        $userDepartments = $qb->select('ud')
            ->from(UserDepartment::class, 'ud')
            ->where($qb->expr()->in('ud.departmentId', ':departmentIds'))
            ->setParameter('departmentIds', $departmentIds)
            ->getQuery()
            ->getResult();

        $userIds = array_map(static fn (UserDepartment $ud) => $ud->getUserId(), $userDepartments);

        return array_values(array_unique($userIds));
    }
}
