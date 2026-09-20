<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Shift;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineShiftReadRepository implements ShiftReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Shift[]
     */
    public function findByUserIdInRange(string $userId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('s')
            ->from(Shift::class, 's')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('s.userId', ':userId'),
                $qb->expr()->lt('s.startAt', ':to'),
                $qb->expr()->gt('s.endAt', ':from')
            ))
            ->setParameter('userId', $userId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Shift[]
     */
    public function findFutureByUserId(string $userId, \DateTimeInterface $from): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('s')
            ->from(Shift::class, 's')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('s.userId', ':userId'),
                $qb->expr()->gt('s.endAt', ':from')
            ))
            ->setParameter('userId', $userId)
            ->setParameter('from', $from)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Shift[]
     */
    public function findAllInRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('s')
            ->from(Shift::class, 's')
            ->where($qb->expr()->andX(
                $qb->expr()->lt('s.startAt', ':to'),
                $qb->expr()->gt('s.endAt', ':from')
            ))
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $userIds
     * @return Shift[]
     */
    public function findByUserIdsInRange(array $userIds, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        if ([] === $userIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('s')
            ->from(Shift::class, 's')
            ->where($qb->expr()->andX(
                $qb->expr()->in('s.userId', ':userIds'),
                $qb->expr()->lt('s.startAt', ':to'),
                $qb->expr()->gt('s.endAt', ':from')
            ))
            ->setParameter('userIds', $userIds)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Shift[]
     */
    public function findCoveringRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('s')
            ->from(Shift::class, 's')
            ->where($qb->expr()->andX(
                $qb->expr()->lte('s.startAt', ':start'),
                $qb->expr()->gte('s.endAt', ':end')
            ))
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.userId', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
