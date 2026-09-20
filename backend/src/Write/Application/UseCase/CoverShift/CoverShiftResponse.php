<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CoverShift;

class CoverShiftResponse
{
    public function __construct(
        public readonly string $originalShiftId,
        public readonly string $originalShiftEndAt,
        public readonly string $newShiftId,
        public readonly string $newShiftUserId,
        public readonly string $newShiftStartAt,
        public readonly string $newShiftEndAt,
        public readonly int $activitiesReassigned,
        public readonly int $activitiesSkipped
    ) {}

    public function serialize(): array
    {
        return [
            'originalShiftId' => $this->originalShiftId,
            'originalShiftEndAt' => $this->originalShiftEndAt,
            'newShiftId' => $this->newShiftId,
            'newShiftUserId' => $this->newShiftUserId,
            'newShiftStartAt' => $this->newShiftStartAt,
            'newShiftEndAt' => $this->newShiftEndAt,
            'activitiesReassigned' => $this->activitiesReassigned,
            'activitiesSkipped' => $this->activitiesSkipped,
        ];
    }
}
