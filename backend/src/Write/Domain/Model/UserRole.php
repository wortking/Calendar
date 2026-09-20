<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use Symfony\Component\Uid\Uuid;

/**
 * Una fila de la tabla de unión user_roles. Sin relación ORM formal hacia
 * User/Role (el proyecto evita asociaciones Doctrine entre agregados): es
 * su propia entidad, con solo los dos ids como campos escalares.
 */
class UserRole
{
    private Uuid $userId;
    private Uuid $roleId;

    public function __construct(string $userId, string $roleId)
    {
        $this->userId = Uuid::fromString($userId);
        $this->roleId = Uuid::fromString($roleId);
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getRoleId(): string
    {
        return $this->roleId->toRfc4122();
    }
}
