<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRoom;

class CreateRoomResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->companyId,
            'name' => $this->name,
        ];
    }
}
