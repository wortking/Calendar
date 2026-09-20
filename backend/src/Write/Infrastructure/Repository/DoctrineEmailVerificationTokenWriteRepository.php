<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Repository;

use App\Write\Domain\Model\EmailVerificationToken;
use App\Write\Domain\Repository\EmailVerificationTokenWriteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineEmailVerificationTokenWriteRepository implements EmailVerificationTokenWriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(EmailVerificationToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function consume(string $tokenHash): ?string
    {
        $token = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(EmailVerificationToken::class, 't')
            ->where('t.tokenHash = :tokenHash')
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $token) {
            return null;
        }

        $userId = $token->getUserId();
        $token->markAsUsed();
        $this->entityManager->flush();

        return $userId;
    }
}
