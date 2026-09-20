<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListDepartmentShifts;

class ListDepartmentShiftsQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to
    ) {}
}
