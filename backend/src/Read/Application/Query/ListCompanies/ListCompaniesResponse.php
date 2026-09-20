<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListCompanies;

class ListCompaniesResponse
{
    /**
     * @param CompanyView[] $companies
     */
    public function __construct(
        public readonly array $companies
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (CompanyView $company) => $company->serialize(), $this->companies);
    }
}
