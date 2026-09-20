<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CopyWeekShifts;

class CopyWeekShiftsResponse
{
    public function __construct(
        public readonly int $copied,
        public readonly int $skipped,
        public readonly int $activitiesCopied = 0,
        public readonly int $activitiesSkipped = 0
    ) {}

    public function serialize(): array
    {
        return [
            'copied' => $this->copied,
            'skipped' => $this->skipped,
            'activitiesCopied' => $this->activitiesCopied,
            'activitiesSkipped' => $this->activitiesSkipped,
        ];
    }
}
