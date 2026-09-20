<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\ActivityType;
use App\Write\Domain\Repository\ActivityTypeWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineActivityTypeWriteRepository implements ActivityTypeWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(ActivityType $activityType): void
    {
        $this->entityManager->persist($activityType);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?ActivityType
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(ActivityType::class, $id);
    }

    public function delete(ActivityType $activityType): void
    {
        $this->entityManager->remove($activityType);
        $this->entityManager->flush();
    }
}
