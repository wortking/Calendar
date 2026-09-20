<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class Department
{
    private Uuid $id;
    private string $name;
    private ?string $description;
    private Uuid $companyId;

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
}
