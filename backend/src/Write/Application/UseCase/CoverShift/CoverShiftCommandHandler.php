<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CoverShift;

use App\Shared\Application\Security\CompanyHoursResolver;
use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\Shift;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Cubrir el resto de un turno: cuando alguien tiene que dejar su turno
 * antes de tiempo (ej. una urgencia médica), esto en un solo paso (a)
 * acorta el turno original a la hora de corte, (b) crea un turno nuevo
 * para el reemplazante desde esa hora hasta el fin original, y (c)
 * reasigna al reemplazante las actividades de sala que el empleado
 * original tenía agendadas en la franja que abandona (se omiten -sin
 * fallar toda la operación- las que el reemplazante ya no puede tomar
 * por tener otro compromiso a esa hora).
 */
#[AsMessageHandler]
class CoverShiftCommandHandler
{
    public function __construct(
        private ShiftWriteRepositoryInterface $shiftWriteRepository,
        private UserReadRepositoryInterface $userRepository,
        private RoomActivityReadRepositoryInterface $roomActivityReadRepository,
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CompanyHoursResolver $companyHoursResolver
    ) {}

    public function __invoke(CoverShiftCommand $command): CoverShiftResponse
    {
        $shift = $this->shiftWriteRepository->findById($command->shiftId);
        if (null === $shift) {
            throw new TranslatableException('handler.shift.not_found', ['%id%' => $command->shiftId]);
        }

        if (null === $this->userRepository->findById($command->replacementUserId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->replacementUserId]);
        }

        if ($command->replacementUserId === $shift->getUserId()) {
            throw new TranslatableException('handler.cover_shift.same_user');
        }

        $this->scopeGuard->assertCanManageUser($command->actingUserId, $shift->getUserId());
        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->replacementUserId);

        $cutoffAt = $this->parseDateTime($command->cutoffAt);
        $originalUserId = $shift->getUserId();
        $originalStart = $shift->getStartAt();
        $originalEnd = $shift->getEndAt();

        if ($cutoffAt <= $originalStart || $cutoffAt >= $originalEnd) {
            throw new TranslatableException('handler.cover_shift.invalid_cutoff');
        }

        // (a) acortar el turno original
        $shift->reschedule($originalUserId, $originalStart, $cutoffAt);
        $this->shiftWriteRepository->save($shift);

        // (b) crear el turno del reemplazante para lo que queda
        $this->assertWithinBusinessHours($command->replacementUserId, $cutoffAt, $originalEnd);
        $newShift = new Shift(Uuid::v7()->toRfc4122(), $command->replacementUserId, $cutoffAt, $originalEnd);
        $this->shiftWriteRepository->save($newShift);

        // (c) reasignar al reemplazante las actividades que quedaron sin turno
        [$reassigned, $skipped] = $this->reassignActivities($originalUserId, $command->replacementUserId, $cutoffAt, $originalEnd);

        return new CoverShiftResponse(
            $shift->getId(),
            $shift->getEndAt()->format('Y-m-d\TH:i:s'),
            $newShift->getId(),
            $newShift->getUserId(),
            $newShift->getStartAt()->format('Y-m-d\TH:i:s'),
            $newShift->getEndAt()->format('Y-m-d\TH:i:s'),
            $reassigned,
            $skipped
        );
    }

    /**
     * @return array{0: int, 1: int} [reasignadas, omitidas]
     */
    private function reassignActivities(string $originalUserId, string $replacementUserId, \DateTimeImmutable $cutoffAt, \DateTimeImmutable $originalEnd): array
    {
        $affected = $this->roomActivityReadRepository->findByUserIdInRange($originalUserId, $cutoffAt, $originalEnd);

        $reassigned = 0;
        $skipped = 0;

        foreach ($affected as $activity) {
            $hasConflict = false;
            foreach ($this->roomActivityReadRepository->findByUserIdInRange($replacementUserId, $activity->getStartAt(), $activity->getEndAt()) as $conflict) {
                $hasConflict = true;
                break;
            }

            if ($hasConflict) {
                ++$skipped;
                continue;
            }

            $writeActivity = $this->roomActivityWriteRepository->findById($activity->getId());
            if (null === $writeActivity) {
                ++$skipped;
                continue;
            }

            $writeActivity->update(
                $activity->getRoomId(),
                $activity->getActivityTypeId(),
                $replacementUserId,
                $activity->getStartAt(),
                $activity->getEndAt()
            );
            $this->roomActivityWriteRepository->save($writeActivity);
            ++$reassigned;
        }

        return [$reassigned, $skipped];
    }

    private function parseDateTime(string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new TranslatableException('domain.shift.invalid_date');
        }
    }

    private function assertWithinBusinessHours(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): void
    {
        $company = $this->companyHoursResolver->resolveForUser($userId);

        if (null === $company) {
            return;
        }

        if ($startAt->format('H:i:s') < $company->getOpeningTime()->format('H:i:s')
            || $endAt->format('H:i:s') > $company->getClosingTime()->format('H:i:s')) {
            throw new TranslatableException('domain.shift.outside_business_hours', [
                '%opening%' => $company->getOpeningTime()->format('H:i'),
                '%closing%' => $company->getClosingTime()->format('H:i'),
            ]);
        }
    }
}
