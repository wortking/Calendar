<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\Shift;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineShiftWriteRepository implements ShiftWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Shift $shift): void
    {
        $this->entityManager->persist($shift);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Shift
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Shift::class, $id);
    }

    public function delete(Shift $shift): void
    {
        $this->entityManager->remove($shift);
        $this->entityManager->flush();
    }
}
