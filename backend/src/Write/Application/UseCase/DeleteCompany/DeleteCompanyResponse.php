<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteCompany;

class DeleteCompanyResponse
{
    public function __construct(
        public readonly string $id
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
