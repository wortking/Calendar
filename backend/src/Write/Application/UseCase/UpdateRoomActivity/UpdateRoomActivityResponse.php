<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateRoomActivity;

class UpdateRoomActivityResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $roomId,
        public readonly string $activityTypeId,
        public readonly string $userId,
        public readonly string $startAt,
        public readonly string $endAt
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'roomId' => $this->roomId,
            'activityTypeId' => $this->activityTypeId,
            'userId' => $this->userId,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
        ];
    }
}
