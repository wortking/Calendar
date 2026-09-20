<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\RoomActivity;

interface RoomActivityWriteRepositoryInterface
{
    public function save(RoomActivity $roomActivity): void;

    public function findById(string $id): ?RoomActivity;

    public function delete(RoomActivity $roomActivity): void;
}
