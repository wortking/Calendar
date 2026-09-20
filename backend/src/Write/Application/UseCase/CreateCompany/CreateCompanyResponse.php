<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateCompany;

class CreateCompanyResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $openingTime,
        public readonly string $closingTime
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'openingTime' => $this->openingTime,
            'closingTime' => $this->closingTime,
        ];
    }
}
