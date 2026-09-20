<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeactivateUser;

class DeactivateUserResponse
{
    public function __construct(
        public readonly string $userId,
        public readonly int $shiftsCancelled,
        public readonly int $activitiesReassigned,
        public readonly int $activitiesSkipped
    ) {}

    public function serialize(): array
    {
        return [
            'userId' => $this->userId,
            'shiftsCancelled' => $this->shiftsCancelled,
            'activitiesReassigned' => $this->activitiesReassigned,
            'activitiesSkipped' => $this->activitiesSkipped,
        ];
    }
}
