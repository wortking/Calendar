<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Company;
use App\Write\Domain\Repository\CompanyWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineCompanyWriteRepository implements CompanyWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Company $company): void
    {
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Company
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Company::class, $id);
    }

    public function delete(Company $company): void
    {
        $this->entityManager->remove($company);
        $this->entityManager->flush();
    }
}
