<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Department;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineDepartmentReadRepository implements DepartmentReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findByName(string $name): ?Department
    {
        return $this->entityManager->getRepository(Department::class)->findOneBy(['name' => $name]);
    }

    public function findById(string $id): ?Department
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Department::class, $id);
    }

    /**
     * @return Department[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Department::class)->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return Department[]
     */
    public function findByCompanyId(string $companyId): array
    {
        return $this->entityManager->getRepository(Department::class)->findBy(['companyId' => $companyId], ['name' => 'ASC']);
    }
}
