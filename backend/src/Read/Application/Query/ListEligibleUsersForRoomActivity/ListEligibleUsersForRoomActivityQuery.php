<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListEligibleUsersForRoomActivity;

class ListEligibleUsersForRoomActivityQuery
{
    public function __construct(
        public readonly \DateTimeImmutable $startAt,
        public readonly \DateTimeImmutable $endAt,
        public readonly ?string $excludeRoomActivityId = null
    ) {}
}
