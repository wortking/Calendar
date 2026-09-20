<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoomActivities;

class ListRoomActivitiesQuery
{
    public function __construct(
        public readonly string $roomId,
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to
    ) {}
}
