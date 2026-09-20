<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\RoomActivity;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoomActivityWriteRepository implements RoomActivityWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(RoomActivity $roomActivity): void
    {
        $this->entityManager->persist($roomActivity);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?RoomActivity
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(RoomActivity::class, $id);
    }

    public function delete(RoomActivity $roomActivity): void
    {
        $this->entityManager->remove($roomActivity);
        $this->entityManager->flush();
    }
}
