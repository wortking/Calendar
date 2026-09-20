<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\RoomActivity;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineRoomActivityReadRepository implements RoomActivityReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?RoomActivity
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(RoomActivity::class, $id);
    }

    /**
     * @return RoomActivity[]
     */
    public function findByRoomInRange(string $roomId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('ra')
            ->from(RoomActivity::class, 'ra')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('ra.roomId', ':roomId'),
                $qb->expr()->lt('ra.startAt', ':to'),
                $qb->expr()->gt('ra.endAt', ':from')
            ))
            ->setParameter('roomId', $roomId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ra.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RoomActivity[]
     */
    public function findByRoomId(string $roomId): array
    {
        return $this->entityManager->getRepository(RoomActivity::class)->findBy(['roomId' => $roomId]);
    }

    /**
     * @return RoomActivity[]
     */
    public function findByUserIdInRange(string $userId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('ra')
            ->from(RoomActivity::class, 'ra')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('ra.userId', ':userId'),
                $qb->expr()->lt('ra.startAt', ':to'),
                $qb->expr()->gt('ra.endAt', ':from')
            ))
            ->setParameter('userId', $userId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ra.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $userIds
     * @return RoomActivity[]
     */
    public function findByUserIdsInRange(array $userIds, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        if ([] === $userIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('ra')
            ->from(RoomActivity::class, 'ra')
            ->where($qb->expr()->andX(
                $qb->expr()->in('ra.userId', ':userIds'),
                $qb->expr()->lt('ra.startAt', ':to'),
                $qb->expr()->gt('ra.endAt', ':from')
            ))
            ->setParameter('userIds', $userIds)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ra.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RoomActivity[]
     */
    public function findAllInRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('ra')
            ->from(RoomActivity::class, 'ra')
            ->where($qb->expr()->andX(
                $qb->expr()->lt('ra.startAt', ':to'),
                $qb->expr()->gt('ra.endAt', ':from')
            ))
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ra.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
