<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\UserDepartment;
use App\Write\Domain\Repository\DepartmentAssignmentWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDepartmentAssignmentWriteRepository implements DepartmentAssignmentWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function assignDepartment(string $userId, string $departmentId): void
    {
        $exists = $this->entityManager->find(UserDepartment::class, ['userId' => $userId, 'departmentId' => $departmentId]);

        if (null !== $exists) {
            return;
        }

        $this->entityManager->persist(new UserDepartment($userId, $departmentId));
        $this->entityManager->flush();
    }

    public function revokeDepartment(string $userId, string $departmentId): void
    {
        $userDepartment = $this->entityManager->find(UserDepartment::class, ['userId' => $userId, 'departmentId' => $departmentId]);

        if (null === $userDepartment) {
            return;
        }

        $this->entityManager->remove($userDepartment);
        $this->entityManager->flush();
    }
}
