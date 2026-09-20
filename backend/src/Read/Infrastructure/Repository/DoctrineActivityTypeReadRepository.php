<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\ActivityType;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineActivityTypeReadRepository implements ActivityTypeReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?ActivityType
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(ActivityType::class, $id);
    }

    public function findByDepartmentIdAndName(string $departmentId, string $name): ?ActivityType
    {
        return $this->entityManager->getRepository(ActivityType::class)->findOneBy([
            'departmentId' => $departmentId,
            'name' => $name,
        ]);
    }

    /**
     * @return ActivityType[]
     */
    public function findByDepartmentId(string $departmentId): array
    {
        return $this->entityManager->getRepository(ActivityType::class)->findBy(
            ['departmentId' => $departmentId],
            ['name' => 'ASC']
        );
    }

    /**
     * @param string[] $departmentIds
     * @return ActivityType[]
     */
    public function findByDepartmentIds(array $departmentIds): array
    {
        if ([] === $departmentIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('a')
            ->from(ActivityType::class, 'a')
            ->where($qb->expr()->in('a.departmentId', ':departmentIds'))
            ->setParameter('departmentIds', $departmentIds)
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ActivityType[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(ActivityType::class)->findBy([], ['name' => 'ASC']);
    }
}
