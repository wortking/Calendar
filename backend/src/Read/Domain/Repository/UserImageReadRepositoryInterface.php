<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\UserImage;

interface UserImageReadRepositoryInterface
{
    public function findById(string $id): ?UserImage;

    /**
     * @return UserImage[]
     */
    public function findByUserId(string $userId): array;
}
