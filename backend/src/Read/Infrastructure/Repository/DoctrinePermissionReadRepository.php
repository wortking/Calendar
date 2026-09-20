<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Permission;
use App\Read\Domain\Repository\PermissionReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrinePermissionReadRepository implements PermissionReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?Permission
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Permission::class, $id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->entityManager->getRepository(Permission::class)->findOneBy(['name' => $name]);
    }

    /**
     * @return Permission[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Permission::class)->findBy([], ['name' => 'ASC']);
    }
}
