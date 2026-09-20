<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoomActivities;

class ListRoomActivitiesResponse
{
    /**
     * @param RoomActivityView[] $items
     */
    public function __construct(
        public readonly array $items
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (RoomActivityView $item) => $item->serialize(), $this->items);
    }
}
