<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\User;
use App\Read\Domain\Repository\RoleAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineUserReadRepository implements UserReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleAssignmentReadRepositoryInterface $roleAssignmentRepository
    ) {}

    public function findById(string $id): ?User
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        $user = $this->entityManager->find(User::class, $id);

        return $this->withRolesAndPermissions($user);
    }

    public function findByEmail(string $email): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        return $this->withRolesAndPermissions($user);
    }

    /**
     * @param string[]|null $ids
     * @return User[]
     */
    public function findPageFiltered(?array $ids, ?string $emailQuery, int $page, int $limit): array
    {
        if (null !== $ids && [] === $ids) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        $this->applyFilters($qb, $ids, $emailQuery);

        $users = $qb->orderBy('u.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $this->withRolesAndPermissions($user);
        }

        return $users;
    }

    /**
     * @param string[]|null $ids
     */
    public function countFiltered(?array $ids, ?string $emailQuery): int
    {
        if (null !== $ids && [] === $ids) {
            return 0;
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u');

        $this->applyFilters($qb, $ids, $emailQuery);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string[]|null $ids
     */
    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, ?array $ids, ?string $emailQuery): void
    {
        if (null !== $ids) {
            $qb->andWhere($qb->expr()->in('u.id', ':ids'))->setParameter('ids', $ids);
        }

        if (null !== $emailQuery && '' !== $emailQuery) {
            $qb->andWhere('LOWER(u.email) LIKE :emailQuery')
                ->setParameter('emailQuery', '%'.strtolower($emailQuery).'%');
        }
    }

    private function withRolesAndPermissions(?User $user): ?User
    {
        if (null === $user) {
            return null;
        }

        $user->setRoleNames($this->roleAssignmentRepository->findRoleNamesByUserId($user->getId()));
        $user->setPermissionNames($this->roleAssignmentRepository->findPermissionNamesByUserId($user->getId()));

        return $user;
    }
}
