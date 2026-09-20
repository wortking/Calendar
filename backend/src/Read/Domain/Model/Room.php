<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class Room
{
    private Uuid $id;
    private Uuid $companyId;
    private string $name;

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getCompanyId(): string
    {
        return $this->companyId->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }
}
