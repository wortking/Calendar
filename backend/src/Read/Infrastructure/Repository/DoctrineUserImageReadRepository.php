<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\UserImage;
use App\Read\Domain\Repository\UserImageReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineUserImageReadRepository implements UserImageReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?UserImage
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(UserImage::class, $id);
    }

    /**
     * @return UserImage[]
     */
    public function findByUserId(string $userId): array
    {
        if (!Uuid::isValid($userId)) {
            return [];
        }

        return $this->entityManager->getRepository(UserImage::class)->findBy(['userId' => $userId]);
    }
}
