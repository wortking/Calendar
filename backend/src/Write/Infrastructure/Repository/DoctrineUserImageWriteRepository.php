<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\UserImage;
use App\Write\Domain\Repository\UserImageWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserImageWriteRepository implements UserImageWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(UserImage $image): void
    {
        $this->entityManager->persist($image);
        $this->entityManager->flush();
    }

    public function deactivateAllForUser(string $userId): void
    {
        $this->entityManager->createQuery(
            'UPDATE App\Write\Domain\Model\UserImage i SET i.isActive = false WHERE i.userId = :userId'
        )->setParameter('userId', $userId)->execute();
    }

    public function activate(string $imageId): void
    {
        $image = $this->entityManager->find(UserImage::class, $imageId);

        if (null === $image) {
            return;
        }

        $image->activate();
        $this->entityManager->flush();
    }
}
