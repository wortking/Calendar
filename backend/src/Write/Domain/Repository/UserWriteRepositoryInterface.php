<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\User;

interface UserWriteRepositoryInterface
{
    public function save(User $user): void;

    public function changePassword(string $userId, string $newHashedPassword): void;

    public function recordLogin(string $userId, \DateTimeImmutable $at): void;

    public function markEmailVerified(string $userId, \DateTimeImmutable $at): void;

    public function updateProfile(string $userId, ?string $firstName, ?string $lastName, ?string $dni, ?string $sex): void;

    public function deactivate(string $userId, \DateTimeImmutable $at, string $byUserId): void;
}
