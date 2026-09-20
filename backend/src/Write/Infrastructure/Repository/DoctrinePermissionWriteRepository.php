<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Permission;
use App\Write\Domain\Repository\PermissionWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrinePermissionWriteRepository implements PermissionWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Permission $permission): void
    {
        $this->entityManager->persist($permission);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Permission
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Permission::class, $id);
    }
}
