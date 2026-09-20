<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Role;
use App\Write\Domain\Repository\RoleWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoleWriteRepository implements RoleWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Role
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Role::class, $id);
    }
}
