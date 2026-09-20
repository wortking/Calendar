<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateCompany;

use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\CompanyWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCompanyCommandHandler
{
    public function __construct(
        private CompanyWriteRepositoryInterface $companyWriteRepository
    ) {}

    public function __invoke(UpdateCompanyCommand $command): UpdateCompanyResponse
    {
        $company = $this->companyWriteRepository->findById($command->id);

        if (null === $company) {
            throw new TranslatableException('handler.company.not_found', ['%id%' => $command->id]);
        }

        $openingTime = $this->parseTime($command->openingTime);
        $closingTime = $this->parseTime($command->closingTime);

        $company->update($command->name, $openingTime, $closingTime);

        $this->companyWriteRepository->save($company);

        return new UpdateCompanyResponse(
            $company->getId(),
            $company->getName(),
            $company->getOpeningTime()->format('H:i'),
            $company->getClosingTime()->format('H:i')
        );
    }

    private function parseTime(string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new TranslatableException('domain.company.invalid_hours');
        }
    }
}
