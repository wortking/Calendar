<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Room;
use App\Write\Domain\Repository\RoomWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoomWriteRepository implements RoomWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Room $room): void
    {
        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Room
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Room::class, $id);
    }

    public function delete(Room $room): void
    {
        $this->entityManager->remove($room);
        $this->entityManager->flush();
    }
}
