<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class Department
{
    private Uuid $id;
    private string $name;
    private ?string $description;
    private Uuid $companyId;

    public function __construct(string $id, string $name, ?string $description, string $companyId)
    {
        if (empty($name)) {
            throw new TranslatableException('domain.department.name_blank');
        }

        if (empty($companyId)) {
            throw new TranslatableException('domain.department.company_required');
        }

        $this->id = Uuid::fromString($id);
        $this->name = $name;
        $this->description = $description;
        $this->companyId = Uuid::fromString($companyId);
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCompanyId(): string
    {
        return $this->companyId->toRfc4122();
    }

    public function rename(string $name): void
    {
        if (empty($name)) {
            throw new TranslatableException('domain.department.name_blank');
        }

        $this->name = $name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function changeCompany(string $companyId): void
    {
        if (empty($companyId)) {
            throw new TranslatableException('domain.department.company_required');
        }

        $this->companyId = Uuid::fromString($companyId);
    }
}
