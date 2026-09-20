<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Repository;

use App\Read\Domain\Model\Permission;
use App\Read\Domain\Model\Role;
use App\Read\Domain\Model\RolePermission;
use App\Read\Domain\Model\UserRole;
use App\Read\Domain\Repository\RoleAssignmentReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * user_roles y role_permissions tienen su propia entidad (UserRole /
 * RolePermission), pero sin relación ORM formal hacia User/Role/Permission
 * (el proyecto evita asociaciones Doctrine entre agregados). El "join" se
 * hace con varias raíces en la misma QueryBuilder y una condición WHERE que
 * las cruza, en vez de un ->join() sobre una asociación mapeada.
 */
class DoctrineRoleAssignmentReadRepository implements RoleAssignmentReadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return string[]
     */
    public function findRoleNamesByUserId(string $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $roles = $qb->select('r')
            ->from(Role::class, 'r')
            ->from(UserRole::class, 'ur')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('ur.roleId', 'r.id'),
                $qb->expr()->eq('ur.userId', ':userId')
            ))
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return array_map(static fn (Role $role) => $role->getName(), $roles);
    }

    /**
     * @return string[]
     */
    public function findPermissionNamesByUserId(string $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $permissions = $qb->select('p')
            ->distinct()
            ->from(Permission::class, 'p')
            ->from(RolePermission::class, 'rp')
            ->from(UserRole::class, 'ur')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('rp.permissionId', 'p.id'),
                $qb->expr()->eq('rp.roleId', 'ur.roleId'),
                $qb->expr()->eq('ur.userId', ':userId')
            ))
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return array_map(static fn (Permission $permission) => $permission->getName(), $permissions);
    }

    public function userHasRole(string $userId, string $roleId): bool
    {
        return null !== $this->entityManager->find(UserRole::class, ['userId' => $userId, 'roleId' => $roleId]);
    }

    /**
     * @return string[]
     */
    public function findPermissionIdsByRoleId(string $roleId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $rolePermissions = $qb->select('rp')
            ->from(RolePermission::class, 'rp')
            ->where($qb->expr()->eq('rp.roleId', ':roleId'))
            ->setParameter('roleId', $roleId)
            ->getQuery()
            ->getResult();

        return array_map(static fn (RolePermission $rolePermission) => $rolePermission->getPermissionId(), $rolePermissions);
    }
}
