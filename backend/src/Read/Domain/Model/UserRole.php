<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class UserRole
{
    private Uuid $userId;
    private Uuid $roleId;

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getRoleId(): string
    {
        return $this->roleId->toRfc4122();
    }
}
