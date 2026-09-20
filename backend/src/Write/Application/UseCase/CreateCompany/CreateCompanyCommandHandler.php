<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateCompany;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Write\Domain\Model\Company;
use App\Write\Domain\Repository\CompanyWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateCompanyCommandHandler
{
    public function __construct(
        private CompanyReadRepositoryInterface $companyReadRepository,
        private CompanyWriteRepositoryInterface $companyWriteRepository
    ) {}

    public function __invoke(CreateCompanyCommand $command): CreateCompanyResponse
    {
        if (null !== $this->companyReadRepository->findByName($command->name)) {
            throw new TranslatableException('handler.company.name_taken', ['%name%' => $command->name]);
        }

        $openingTime = $this->parseTime($command->openingTime);
        $closingTime = $this->parseTime($command->closingTime);

        $id = Uuid::v7()->toRfc4122();
        $company = new Company($id, $command->name, $openingTime, $closingTime);

        $this->companyWriteRepository->save($company);

        return new CreateCompanyResponse(
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
