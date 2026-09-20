<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRooms;

class ListRoomsResponse
{
    /**
     * @param RoomView[] $rooms
     */
    public function __construct(
        public readonly array $rooms
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (RoomView $room) => $room->serialize(), $this->rooms);
    }
}
