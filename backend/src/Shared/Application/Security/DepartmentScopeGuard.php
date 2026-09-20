<?php

declare(strict_types=1);

namespace App\Shared\Application\Security;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\RoleAssignmentReadRepositoryInterface;
use App\Write\Domain\Model\Role;

/**
 * Autorización por alcance de departamento: un Admin puede gestionar
 * cualquier usuario/departamento; un Coordinador solo los que comparten
 * departamento con él (y solo puede asignar/quitar el rol base ROLE_USER,
 * nunca ROLE_ADMIN/ROLE_COORDINADOR). Se usa desde los handlers de escritura
 * (no alcanza con el #[IsGranted] de la ruta, que solo sabe si el usuario
 * tiene el permiso, no sobre quién lo puede ejercer).
 */
class DepartmentScopeGuard
{
    public function __construct(
        private RoleAssignmentReadRepositoryInterface $roleAssignmentRepository,
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository
    ) {}

    public function isAdmin(string $userId): bool
    {
        return in_array(Role::ROLE_ADMIN, $this->roleAssignmentRepository->findRoleNamesByUserId($userId), true);
    }

    public function assertCanManageUser(string $actingUserId, string $targetUserId): void
    {
        if ($this->isAdmin($actingUserId) || $actingUserId === $targetUserId) {
            return;
        }

        $shared = array_intersect(
            $this->departmentIdsOf($actingUserId),
            $this->departmentIdsOf($targetUserId)
        );

        if ([] === $shared) {
            throw new TranslatableException('handler.access_denied.department_scope');
        }
    }

    public function assertOwnsDepartment(string $actingUserId, string $departmentId): void
    {
        if ($this->isAdmin($actingUserId)) {
            return;
        }

        if (!in_array($departmentId, $this->departmentIdsOf($actingUserId), true)) {
            throw new TranslatableException('handler.access_denied.department_scope');
        }
    }

    public function assertCanAssignRole(string $actingUserId, string $roleName): void
    {
        if ($this->isAdmin($actingUserId)) {
            return;
        }

        if (Role::ROLE_USER !== $roleName) {
            throw new TranslatableException('handler.access_denied.role_scope');
        }
    }

    /**
     * @return string[]|null null = sin restricción (admin)
     */
    public function scopedUserIdsFor(string $actingUserId): ?array
    {
        if ($this->isAdmin($actingUserId)) {
            return null;
        }

        $departmentIds = $this->departmentIdsOf($actingUserId);

        $userIds = [] !== $departmentIds
            ? $this->departmentAssignmentRepository->findUserIdsByDepartmentIds($departmentIds)
            : [];

        return array_values(array_unique([...$userIds, $actingUserId]));
    }

    /**
     * @return string[]
     */
    private function departmentIdsOf(string $userId): array
    {
        return array_map(
            static fn ($department) => $department->getId(),
            $this->departmentAssignmentRepository->findDepartmentsByUserId($userId)
        );
    }
}
