<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateShift;

class UpdateShiftResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $startAt,
        public readonly string $endAt
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
        ];
    }
}
