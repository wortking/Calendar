<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\UserImage;

interface UserImageWriteRepositoryInterface
{
    public function save(UserImage $image): void;

    public function deactivateAllForUser(string $userId): void;

    public function activate(string $imageId): void;
}
