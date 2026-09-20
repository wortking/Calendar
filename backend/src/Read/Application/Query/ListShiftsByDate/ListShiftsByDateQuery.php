<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListShiftsByDate;

class ListShiftsByDateQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to
    ) {}
}
