<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\RefreshToken;
use App\Write\Domain\Repository\RefreshTokenWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineRefreshTokenWriteRepository implements RefreshTokenWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(RefreshToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function consume(string $tokenHash): ?string
    {
        $token = $this->findValid($tokenHash);

        if (null === $token) {
            return null;
        }

        $userId = $token->getUserId();
        $token->revoke();
        $this->entityManager->flush();

        return $userId;
    }

    public function revoke(string $tokenHash): void
    {
        $token = $this->findValid($tokenHash);

        if (null === $token) {
            return;
        }

        $token->revoke();
        $this->entityManager->flush();
    }

    private function findValid(string $tokenHash): ?RefreshToken
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(RefreshToken::class, 't')
            ->where('t.tokenHash = :tokenHash')
            ->andWhere('t.revokedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
