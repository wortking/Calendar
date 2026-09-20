<?php

declare(strict_types=1);

namespace App\Read\Application\Query;

/**
 * Actividad puntual de sala que cae dentro del horario de un turno,
 * anidada en la respuesta de ese turno (ListMyShifts, ListShiftsByDate,
 * ListDepartmentShifts).
 */
class RoomActivityOfShiftView
{
    public function __construct(
        public readonly string $id,
        public readonly string $roomId,
        public readonly ?string $roomName,
        public readonly string $activityTypeId,
        public readonly ?string $activityTypeName,
        public readonly ?string $activityTypeColor,
        public readonly string $startAt,
        public readonly string $endAt
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'roomId' => $this->roomId,
            'roomName' => $this->roomName,
            'activityTypeId' => $this->activityTypeId,
            'activityTypeName' => $this->activityTypeName,
            'activityTypeColor' => $this->activityTypeColor,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
        ];
    }
}
