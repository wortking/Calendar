<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class RoomActivity
{
    private Uuid $id;
    private Uuid $roomId;
    private Uuid $activityTypeId;
    private Uuid $userId;
    private \DateTimeImmutable $startAt;
    private \DateTimeImmutable $endAt;

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getRoomId(): string
    {
        return $this->roomId->toRfc4122();
    }

    public function getActivityTypeId(): string
    {
        return $this->activityTypeId->toRfc4122();
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }
}
