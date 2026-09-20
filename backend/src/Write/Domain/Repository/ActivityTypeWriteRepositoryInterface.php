<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\ActivityType;

interface ActivityTypeWriteRepositoryInterface
{
    public function save(ActivityType $activityType): void;

    public function findById(string $id): ?ActivityType;

    public function delete(ActivityType $activityType): void;
}
