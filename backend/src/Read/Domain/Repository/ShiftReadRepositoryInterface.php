<?php

declare(strict_types=1);

namespace App\Read\Domain\Repository;

use App\Read\Domain\Model\Shift;

interface ShiftReadRepositoryInterface
{
    /**
     * Turnos del usuario que se solapan con el rango [$from, $to].
     *
     * @return Shift[]
     */
    public function findByUserIdInRange(string $userId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Turnos del usuario que todavía no terminaron (endAt > $from), sin cota
     * superior. Se usa al dar de baja a un usuario para cancelar todo lo
     * pendiente sin tocar el historial ya cumplido.
     *
     * @return Shift[]
     */
    public function findFutureByUserId(string $userId, \DateTimeInterface $from): array;

    /**
     * Turnos de todos los usuarios que se solapan con el rango [$from, $to].
     *
     * @return Shift[]
     */
    public function findAllInRange(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Turnos de los usuarios dados que se solapan con el rango [$from, $to].
     *
     * @param string[] $userIds
     * @return Shift[]
     */
    public function findByUserIdsInRange(array $userIds, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Turnos (de cualquier usuario) que cubren COMPLETAMENTE el rango
     * [$start, $end], es decir startAt <= $start && endAt >= $end. Se usa
     * para saber qué empleados están de turno a una hora exacta, al
     * agendar una actividad puntual en una sala.
     *
     * @return Shift[]
     */
    public function findCoveringRange(\DateTimeInterface $start, \DateTimeInterface $end): array;
}
