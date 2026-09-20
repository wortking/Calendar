<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListCompanies;

use App\Read\Domain\Repository\CompanyReadRepositoryInterface;

class ListCompaniesQueryHandler
{
    public function __construct(
        private CompanyReadRepositoryInterface $companyRepository
    ) {}

    public function __invoke(ListCompaniesQuery $query): ListCompaniesResponse
    {
        $companies = array_map(
            static fn ($company) => new CompanyView(
                $company->getId(),
                $company->getName(),
                $company->getOpeningTime()->format('H:i'),
                $company->getClosingTime()->format('H:i')
            ),
            $this->companyRepository->findAll()
        );

        return new ListCompaniesResponse($companies);
    }
}
