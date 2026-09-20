<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteShift;

class DeleteShiftResponse
{
    public function __construct(
        public readonly string $shiftId
    ) {}

    public function serialize(): array
    {
        return [
            'shiftId' => $this->shiftId,
        ];
    }
}
