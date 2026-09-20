<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoomActivities;

class RoomActivityView
{
    public function __construct(
        public readonly string $id,
        public readonly string $roomId,
        public readonly string $activityTypeId,
        public readonly ?string $activityTypeName,
        public readonly ?string $activityTypeColor,
        public readonly string $userId,
        public readonly string $userEmail,
        public readonly ?string $userName,
        public readonly string $startAt,
        public readonly string $endAt
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'roomId' => $this->roomId,
            'activityTypeId' => $this->activityTypeId,
            'activityTypeName' => $this->activityTypeName,
            'activityTypeColor' => $this->activityTypeColor,
            'userId' => $this->userId,
            'userEmail' => $this->userEmail,
            'userName' => $this->userName,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
        ];
    }
}
