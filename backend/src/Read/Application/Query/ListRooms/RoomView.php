<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRooms;

class RoomView
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
