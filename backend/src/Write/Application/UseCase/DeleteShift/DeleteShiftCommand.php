<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteShift;

class DeleteShiftCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $shiftId,
        public readonly string $actingUserId
    ) {}
}
