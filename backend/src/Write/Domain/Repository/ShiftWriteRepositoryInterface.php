<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Shift;

interface ShiftWriteRepositoryInterface
{
    public function save(Shift $shift): void;

    public function findById(string $id): ?Shift;

    public function delete(Shift $shift): void;
}
