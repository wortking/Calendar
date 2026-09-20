<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\Company;

interface CompanyWriteRepositoryInterface
{
    public function save(Company $company): void;

    public function findById(string $id): ?Company;

    public function delete(Company $company): void;
}
