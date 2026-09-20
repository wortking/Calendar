<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

/**
 * Franja puntual de una actividad dentro de una sala (ej. "Body Pump" en
 * Sala 1 de 10:00 a 11:00), asignada a un empleado que ya tiene un turno
 * que cubre ese horario. Vive aparte del turno: un turno puede tener 0 o
 * varias de estas.
 */
class RoomActivity
{
    private Uuid $id;
    private Uuid $roomId;
    private Uuid $activityTypeId;
    private Uuid $userId;
    private \DateTimeImmutable $startAt;
    private \DateTimeImmutable $endAt;

    public function __construct(string $id, string $roomId, string $activityTypeId, string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt)
    {
        if ($endAt <= $startAt) {
            throw new TranslatableException('domain.room_activity.invalid_range');
        }

        $this->id = Uuid::fromString($id);
        $this->roomId = Uuid::fromString($roomId);
        $this->activityTypeId = Uuid::fromString($activityTypeId);
        $this->userId = Uuid::fromString($userId);
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

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

    public function update(string $roomId, string $activityTypeId, string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): void
    {
        if ($endAt <= $startAt) {
            throw new TranslatableException('domain.room_activity.invalid_range');
        }

        $this->roomId = Uuid::fromString($roomId);
        $this->activityTypeId = Uuid::fromString($activityTypeId);
        $this->userId = Uuid::fromString($userId);
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }
}
