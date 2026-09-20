<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\PasswordResetToken;
use App\Write\Domain\Repository\PasswordResetTokenWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePasswordResetTokenWriteRepository implements PasswordResetTokenWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(PasswordResetToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function consume(string $userId, string $codeHash): bool
    {
        $token = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(PasswordResetToken::class, 't')
            ->where('t.userId = :userId')
            ->andWhere('t.codeHash = :codeHash')
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('codeHash', $codeHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $token) {
            return false;
        }

        $token->markAsUsed();
        $this->entityManager->flush();

        return true;
    }
}
