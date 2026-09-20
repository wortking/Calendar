<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Room;

interface RoomReadRepositoryInterface
{
    public function findById(string $id): ?Room;

    public function findByCompanyIdAndName(string $companyId, string $name): ?Room;

    /**
     * @return Room[]
     */
    public function findByCompanyId(string $companyId): array;

    /**
     * @return Room[]
     */
    public function findAll(): array;
}
