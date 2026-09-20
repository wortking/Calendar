<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\User;

interface UserReadRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param string[]|null $ids null = sin restricción de ids (Admin); un
     *                            array (incluso vacío) restringe a esos ids
     * @return User[]
     */
    public function findPageFiltered(?array $ids, ?string $emailQuery, int $page, int $limit): array;

    /**
     * @param string[]|null $ids
     */
    public function countFiltered(?array $ids, ?string $emailQuery): int;
}
