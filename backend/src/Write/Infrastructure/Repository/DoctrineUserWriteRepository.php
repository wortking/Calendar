<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\User;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineUserWriteRepository implements UserWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function changePassword(string $userId, string $newHashedPassword): void
    {
        if (!Uuid::isValid($userId)) {
            return;
        }

        $user = $this->entityManager->find(User::class, $userId);

        if (null === $user) {
            return;
        }

        $user->changePassword($newHashedPassword);
        $this->entityManager->flush();
    }

    public function recordLogin(string $userId, \DateTimeImmutable $at): void
    {
        if (!Uuid::isValid($userId)) {
            return;
        }

        $user = $this->entityManager->find(User::class, $userId);

        if (null === $user) {
            return;
        }

        $user->recordLogin($at);
        $this->entityManager->flush();
    }

    public function markEmailVerified(string $userId, \DateTimeImmutable $at): void
    {
        if (!Uuid::isValid($userId)) {
            return;
        }

        $user = $this->entityManager->find(User::class, $userId);

        if (null === $user) {
            return;
        }

        $user->verifyEmail($at);
        $this->entityManager->flush();
    }

    public function updateProfile(string $userId, ?string $firstName, ?string $lastName, ?string $dni, ?string $sex): void
    {
        if (!Uuid::isValid($userId)) {
            return;
        }

        $user = $this->entityManager->find(User::class, $userId);

        if (null === $user) {
            return;
        }

        $user->updateProfile($firstName, $lastName, $dni, $sex);
        $this->entityManager->flush();
    }

    public function deactivate(string $userId, \DateTimeImmutable $at, string $byUserId): void
    {
        if (!Uuid::isValid($userId)) {
            return;
        }

        $user = $this->entityManager->find(User::class, $userId);

        if (null === $user) {
            return;
        }

        $user->deactivate($at, $byUserId);
        $this->entityManager->flush();
    }
}
