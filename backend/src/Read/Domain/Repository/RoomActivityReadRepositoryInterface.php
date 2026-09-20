<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\RoomActivity;

interface RoomActivityReadRepositoryInterface
{
    public function findById(string $id): ?RoomActivity;

    /**
     * Actividades de la sala que se solapan con el rango [$from, $to].
     *
     * @return RoomActivity[]
     */
    public function findByRoomInRange(string $roomId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Todas las actividades de la sala, sin acotar por fecha. Se usa al
     * borrar una sala, para borrarlas junto con ella.
     *
     * @return RoomActivity[]
     */
    public function findByRoomId(string $roomId): array;

    /**
     * Actividades del usuario (en cualquier sala) que se solapan con el
     * rango [$from, $to]. Se usa tanto para anidar las actividades dentro
     * de un turno como para evitar que un empleado quede agendado en dos
     * actividades al mismo tiempo.
     *
     * @return RoomActivity[]
     */
    public function findByUserIdInRange(string $userId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Actividades de los usuarios dados (en cualquier sala) que se solapan
     * con el rango [$from, $to]. Se usa al copiar la semana anterior.
     *
     * @param string[] $userIds
     * @return RoomActivity[]
     */
    public function findByUserIdsInRange(array $userIds, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Todas las actividades (de cualquier usuario/sala) que se solapan con
     * el rango [$from, $to]. Se usa al copiar la semana anterior para un
     * actor sin alcance restringido (Admin).
     *
     * @return RoomActivity[]
     */
    public function findAllInRange(\DateTimeInterface $from, \DateTimeInterface $to): array;
}
