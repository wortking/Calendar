<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\UserRole;
use App\Write\Domain\Repository\RoleAssignmentWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRoleAssignmentWriteRepository implements RoleAssignmentWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function assignRole(string $userId, string $roleId): void
    {
        $exists = $this->entityManager->find(UserRole::class, ['userId' => $userId, 'roleId' => $roleId]);

        if (null !== $exists) {
            return;
        }

        $this->entityManager->persist(new UserRole($userId, $roleId));
        $this->entityManager->flush();
    }

    public function revokeRole(string $userId, string $roleId): void
    {
        $userRole = $this->entityManager->find(UserRole::class, ['userId' => $userId, 'roleId' => $roleId]);

        if (null === $userRole) {
            return;
        }

        $this->entityManager->remove($userRole);
        $this->entityManager->flush();
    }
}
