<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListShiftsByDate;

use App\Read\Application\Query\RoomActivityOfShiftView;

class ShiftWithUserView
{
    /**
     * @param RoomActivityOfShiftView[] $roomActivities
     */
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $userEmail,
        public readonly ?string $userName,
        public readonly string $startAt,
        public readonly string $endAt,
        public readonly ?string $avatarUrl,
        public readonly array $roomActivities = []
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'userEmail' => $this->userEmail,
            'userName' => $this->userName,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
            'avatarUrl' => $this->avatarUrl,
            'roomActivities' => array_map(static fn (RoomActivityOfShiftView $item) => $item->serialize(), $this->roomActivities),
        ];
    }
}
