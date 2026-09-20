<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Department;
use App\Write\Domain\Repository\DepartmentWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineDepartmentWriteRepository implements DepartmentWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Department $department): void
    {
        $this->entityManager->persist($department);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Department
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Department::class, $id);
    }

    public function delete(Department $department): void
    {
        $this->entityManager->remove($department);
        $this->entityManager->flush();
    }
}
