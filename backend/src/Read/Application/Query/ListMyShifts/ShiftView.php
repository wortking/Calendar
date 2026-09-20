<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListMyShifts;

use App\Read\Application\Query\RoomActivityOfShiftView;

class ShiftView
{
    /**
     * @param RoomActivityOfShiftView[] $roomActivities
     */
    public function __construct(
        public readonly string $id,
        public readonly string $startAt,
        public readonly string $endAt,
        public readonly array $roomActivities = []
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
            'roomActivities' => array_map(static fn (RoomActivityOfShiftView $item) => $item->serialize(), $this->roomActivities),
        ];
    }
}
