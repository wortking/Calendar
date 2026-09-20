<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Company;

interface CompanyReadRepositoryInterface
{
    public function findByName(string $name): ?Company;

    public function findById(string $id): ?Company;

    /**
     * @return Company[]
     */
    public function findAll(): array;
}
