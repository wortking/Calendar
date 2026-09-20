<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListMyShifts;

class ListMyShiftsResponse
{
    /**
     * @param ShiftView[] $shifts
     */
    public function __construct(
        public readonly array $shifts
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (ShiftView $shift) => $shift->serialize(), $this->shifts);
    }
}
