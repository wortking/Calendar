<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Room;

interface RoomWriteRepositoryInterface
{
    public function save(Room $room): void;

    public function findById(string $id): ?Room;

    public function delete(Room $room): void;
}
