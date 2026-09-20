<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\RolePermission;
use App\Write\Domain\Repository\RolePermissionWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRolePermissionWriteRepository implements RolePermissionWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function assignPermission(string $roleId, string $permissionId): void
    {
        $exists = $this->entityManager->find(RolePermission::class, ['roleId' => $roleId, 'permissionId' => $permissionId]);

        if (null !== $exists) {
            return;
        }

        $this->entityManager->persist(new RolePermission($roleId, $permissionId));
        $this->entityManager->flush();
    }

    public function revokePermission(string $roleId, string $permissionId): void
    {
        $rolePermission = $this->entityManager->find(RolePermission::class, ['roleId' => $roleId, 'permissionId' => $permissionId]);

        if (null === $rolePermission) {
            return;
        }

        $this->entityManager->remove($rolePermission);
        $this->entityManager->flush();
    }
}
