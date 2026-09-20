<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Company;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineCompanyReadRepository implements CompanyReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findByName(string $name): ?Company
    {
        return $this->entityManager->getRepository(Company::class)->findOneBy(['name' => $name]);
    }

    public function findById(string $id): ?Company
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Company::class, $id);
    }

    /**
     * @return Company[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Company::class)->findBy([], ['name' => 'ASC']);
    }
}
