<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use Symfony\Component\Uid\Uuid;

/**
 * Una fila de la tabla de unión user_departments. Sin relación ORM formal
 * hacia User/Department (mismo criterio que UserRole): entidad propia, solo
 * con los dos ids como campos escalares.
 */
class UserDepartment
{
    private Uuid $userId;
    private Uuid $departmentId;

    public function __construct(string $userId, string $departmentId)
    {
        $this->userId = Uuid::fromString($userId);
        $this->departmentId = Uuid::fromString($departmentId);
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId->toRfc4122();
    }
}
