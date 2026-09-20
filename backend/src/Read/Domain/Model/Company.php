<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class Company
{
    private Uuid $id;
    private string $name;
    private \DateTimeImmutable $openingTime;
    private \DateTimeImmutable $closingTime;

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
}
