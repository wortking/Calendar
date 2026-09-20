<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CopyWeekShifts;

use App\Shared\Application\Security\CompanyHoursResolver;
use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use App\Write\Domain\Model\RoomActivity;
use App\Write\Domain\Model\Shift;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Copia los turnos de los 7 días previos a la semana indicada (mismo
 * horario y usuario, corridos +7 días), y opcionalmente también las
 * actividades de sala agendadas en esos días. Pensado para "repetir la
 * semana pasada" desde el dashboard. Un turno (o actividad) se omite (no
 * falla toda la operación) si ya existe uno idéntico en el destino, si el
 * horario ya no entra en el de la empresa del usuario, o -para
 * actividades- si la sala/el empleado ya están ocupados en el destino o el
 * turno correspondiente no se pudo copiar.
 */
#[AsMessageHandler]
class CopyWeekShiftsCommandHandler
{
    public function __construct(
        private ShiftReadRepositoryInterface $shiftReadRepository,
        private ShiftWriteRepositoryInterface $shiftWriteRepository,
        private RoomActivityReadRepositoryInterface $roomActivityReadRepository,
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CompanyHoursResolver $companyHoursResolver
    ) {}

    public function __invoke(CopyWeekShiftsCommand $command): CopyWeekShiftsResponse
    {
        $destStart = $this->parseDate($command->weekStart);
        $destEnd = $destStart->modify('+7 days');
        $sourceStart = $destStart->modify('-7 days');

        $ids = $this->scopeGuard->scopedUserIdsFor($command->actingUserId);

        [$copied, $skipped] = $this->copyShifts($ids, $sourceStart, $destStart, $destEnd);

        $activitiesCopied = 0;
        $activitiesSkipped = 0;

        if ($command->includeRoomActivities) {
            [$activitiesCopied, $activitiesSkipped] = $this->copyRoomActivities($ids, $sourceStart, $destStart, $destEnd);
        }

        return new CopyWeekShiftsResponse($copied, $skipped, $activitiesCopied, $activitiesSkipped);
    }

    /**
     * @param string[]|null $ids
     * @return array{0: int, 1: int} [copiados, omitidos]
     */
    private function copyShifts(?array $ids, \DateTimeImmutable $sourceStart, \DateTimeImmutable $destStart, \DateTimeImmutable $destEnd): array
    {
        $sourceShifts = null === $ids
            ? $this->shiftReadRepository->findAllInRange($sourceStart, $destStart)
            : $this->shiftReadRepository->findByUserIdsInRange($ids, $sourceStart, $destStart);

        $destShifts = null === $ids
            ? $this->shiftReadRepository->findAllInRange($destStart, $destEnd)
            : $this->shiftReadRepository->findByUserIdsInRange($ids, $destStart, $destEnd);

        $existingKeys = [];
        foreach ($destShifts as $shift) {
            $existingKeys[$this->shiftKey($shift->getUserId(), $shift->getStartAt(), $shift->getEndAt())] = true;
        }

        $copied = 0;
        $skipped = 0;

        foreach ($sourceShifts as $shift) {
            $newStart = $shift->getStartAt()->modify('+7 days');
            $newEnd = $shift->getEndAt()->modify('+7 days');
            $key = $this->shiftKey($shift->getUserId(), $newStart, $newEnd);

            if (isset($existingKeys[$key]) || !$this->isWithinBusinessHours($shift->getUserId(), $newStart, $newEnd)) {
                ++$skipped;
                continue;
            }

            $newShift = new Shift(
                Uuid::v7()->toRfc4122(),
                $shift->getUserId(),
                $newStart,
                $newEnd
            );

            $this->shiftWriteRepository->save($newShift);
            $existingKeys[$key] = true;
            ++$copied;
        }

        return [$copied, $skipped];
    }

    /**
     * @param string[]|null $ids
     * @return array{0: int, 1: int} [copiadas, omitidas]
     */
    private function copyRoomActivities(?array $ids, \DateTimeImmutable $sourceStart, \DateTimeImmutable $destStart, \DateTimeImmutable $destEnd): array
    {
        $sourceActivities = null === $ids
            ? $this->roomActivityReadRepository->findAllInRange($sourceStart, $destStart)
            : $this->roomActivityReadRepository->findByUserIdsInRange($ids, $sourceStart, $destStart);

        $destActivities = null === $ids
            ? $this->roomActivityReadRepository->findAllInRange($destStart, $destEnd)
            : $this->roomActivityReadRepository->findByUserIdsInRange($ids, $destStart, $destEnd);

        $roomKeys = [];
        $userKeys = [];
        foreach ($destActivities as $activity) {
            $roomKeys[$this->timeKey($activity->getRoomId(), $activity->getStartAt(), $activity->getEndAt())] = true;
            $userKeys[$this->timeKey($activity->getUserId(), $activity->getStartAt(), $activity->getEndAt())] = true;
        }

        $copied = 0;
        $skipped = 0;

        foreach ($sourceActivities as $activity) {
            $newStart = $activity->getStartAt()->modify('+7 days');
            $newEnd = $activity->getEndAt()->modify('+7 days');
            $roomKey = $this->timeKey($activity->getRoomId(), $newStart, $newEnd);
            $userKey = $this->timeKey($activity->getUserId(), $newStart, $newEnd);

            $hasConflict = isset($roomKeys[$roomKey]) || isset($userKeys[$userKey]);
            $hasCoveringShift = $this->userHasShiftCovering($activity->getUserId(), $newStart, $newEnd);

            if ($hasConflict || !$hasCoveringShift) {
                ++$skipped;
                continue;
            }

            $newActivity = new RoomActivity(
                Uuid::v7()->toRfc4122(),
                $activity->getRoomId(),
                $activity->getActivityTypeId(),
                $activity->getUserId(),
                $newStart,
                $newEnd
            );

            $this->roomActivityWriteRepository->save($newActivity);
            $roomKeys[$roomKey] = true;
            $userKeys[$userKey] = true;
            ++$copied;
        }

        return [$copied, $skipped];
    }

    private function userHasShiftCovering(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): bool
    {
        foreach ($this->shiftReadRepository->findCoveringRange($startAt, $endAt) as $shift) {
            if ($shift->getUserId() === $userId) {
                return true;
            }
        }

        return false;
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new TranslatableException('domain.shift.invalid_date');
        }
    }

    private function shiftKey(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): string
    {
        return $userId.'|'.$startAt->format(DATE_ATOM).'|'.$endAt->format(DATE_ATOM);
    }

    private function timeKey(string $id, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): string
    {
        return $id.'|'.$startAt->format(DATE_ATOM).'|'.$endAt->format(DATE_ATOM);
    }

    private function isWithinBusinessHours(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): bool
    {
        $company = $this->companyHoursResolver->resolveForUser($userId);

        if (null === $company) {
            return true;
        }

        return $startAt->format('H:i:s') >= $company->getOpeningTime()->format('H:i:s')
            && $endAt->format('H:i:s') <= $company->getClosingTime()->format('H:i:s');
    }
}
