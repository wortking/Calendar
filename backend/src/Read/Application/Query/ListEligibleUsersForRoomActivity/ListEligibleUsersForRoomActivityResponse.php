<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListEligibleUsersForRoomActivity;

class ListEligibleUsersForRoomActivityResponse
{
    /**
     * @param EligibleUserView[] $items
     */
    public function __construct(
        public readonly array $items
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (EligibleUserView $item) => $item->serialize(), $this->items);
    }
}
