<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class Company
{
    private Uuid $id;
    private string $name;
    private \DateTimeImmutable $openingTime;
    private \DateTimeImmutable $closingTime;

    public function __construct(string $id, string $name, \DateTimeImmutable $openingTime, \DateTimeImmutable $closingTime)
    {
        if (empty($name)) {
            throw new TranslatableException('domain.company.name_blank');
        }

        if ($closingTime <= $openingTime) {
            throw new TranslatableException('domain.company.invalid_hours');
        }

        $this->id = Uuid::fromString($id);
        $this->name = $name;
        $this->openingTime = $openingTime;
        $this->closingTime = $closingTime;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOpeningTime(): \DateTimeImmutable
    {
        return $this->openingTime;
    }

    public function getClosingTime(): \DateTimeImmutable
    {
        return $this->closingTime;
    }

    public function update(string $name, \DateTimeImmutable $openingTime, \DateTimeImmutable $closingTime): void
    {
        if (empty($name)) {
            throw new TranslatableException('domain.company.name_blank');
        }

        if ($closingTime <= $openingTime) {
            throw new TranslatableException('domain.company.invalid_hours');
        }

        $this->name = $name;
        $this->openingTime = $openingTime;
        $this->closingTime = $closingTime;
    }
}
