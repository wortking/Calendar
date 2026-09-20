<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class UserDepartment
{
    private Uuid $userId;
    private Uuid $departmentId;

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId->toRfc4122();
    }
}
