<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Role;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoleReadRepository implements RoleReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findByName(string $name): ?Role
    {
        return $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $name]);
    }

    public function findById(string $id): ?Role
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Role::class, $id);
    }

    /**
     * @return Role[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Role::class)->findBy([], ['name' => 'ASC']);
    }
}
