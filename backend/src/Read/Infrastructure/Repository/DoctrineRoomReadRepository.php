<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Room;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoomReadRepository implements RoomReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?Room
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Room::class, $id);
    }

    public function findByCompanyIdAndName(string $companyId, string $name): ?Room
    {
        return $this->entityManager->getRepository(Room::class)->findOneBy([
            'companyId' => $companyId,
            'name' => $name,
        ]);
    }

    /**
     * @return Room[]
     */
    public function findByCompanyId(string $companyId): array
    {
        return $this->entityManager->getRepository(Room::class)->findBy(
            ['companyId' => $companyId],
            ['name' => 'ASC']
        );
    }

    /**
     * @return Room[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Room::class)->findBy([], ['name' => 'ASC']);
    }
}
