<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeactivateUser;

use App\Shared\Application\Security\CompanyHoursResolver;
use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\Shift;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Baja lógica de un usuario: nunca se borra el registro (solo se marca
 * deactivatedAt/deactivatedBy), pero (a) se cancelan todos sus turnos
 * pendientes (los ya cumplidos quedan intactos, para no perder el
 * historial de horas trabajadas) y (b) las actividades de sala que tenía
 * agendadas en esos turnos pasan a quien ejecuta la baja, con un turno
 * propio que las cubra -así aparecen en su calendario, igual que al
 * "cubrir turno"-, para que sepa que tiene que reasignarlas a otra
 * persona. El login del usuario dado de baja queda bloqueado (ver
 * App\Shared\Infrastructure\Security\UserChecker).
 */
#[AsMessageHandler]
class DeactivateUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private UserWriteRepositoryInterface $userWriteRepository,
        private ShiftReadRepositoryInterface $shiftReadRepository,
        private ShiftWriteRepositoryInterface $shiftWriteRepository,
        private RoomActivityReadRepositoryInterface $roomActivityReadRepository,
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CompanyHoursResolver $companyHoursResolver
    ) {}

    public function __invoke(DeactivateUserCommand $command): DeactivateUserResponse
    {
        if (null === $this->userReadRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        if ($command->actingUserId === $command->userId) {
            throw new TranslatableException('handler.deactivate_user.self');
        }

        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->userId);

        $now = new \DateTimeImmutable();
        $futureShifts = $this->shiftReadRepository->findFutureByUserId($command->userId, $now);

        $shiftsCancelled = 0;
        $activitiesReassigned = 0;
        $activitiesSkipped = 0;

        foreach ($futureShifts as $shift) {
            [$reassigned, $skipped] = $this->reassignActivities(
                $command->userId,
                $command->actingUserId,
                $shift->getStartAt(),
                $shift->getEndAt()
            );
            $activitiesReassigned += $reassigned;
            $activitiesSkipped += $skipped;

            $writeShift = $this->shiftWriteRepository->findById($shift->getId());
            if (null !== $writeShift) {
                $this->shiftWriteRepository->delete($writeShift);
                ++$shiftsCancelled;
            }
        }

        $this->userWriteRepository->deactivate($command->userId, $now, $command->actingUserId);

        return new DeactivateUserResponse($command->userId, $shiftsCancelled, $activitiesReassigned, $activitiesSkipped);
    }

    /**
     * @return array{0: int, 1: int} [reasignadas, omitidas]
     */
    private function reassignActivities(string $originalUserId, string $coordinatorUserId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): array
    {
        $affected = $this->roomActivityReadRepository->findByUserIdInRange($originalUserId, $startAt, $endAt);

        if ([] === $affected) {
            return [0, 0];
        }

        $reassigned = 0;
        $skipped = 0;
        $coordinatorNeedsShift = false;

        foreach ($affected as $activity) {
            $hasConflict = false;
            foreach ($this->roomActivityReadRepository->findByUserIdInRange($coordinatorUserId, $activity->getStartAt(), $activity->getEndAt()) as $conflict) {
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

            if (!$coordinatorNeedsShift) {
                $this->assertWithinBusinessHours($coordinatorUserId, $startAt, $endAt);
                $coordinatorShift = new Shift(Uuid::v7()->toRfc4122(), $coordinatorUserId, $startAt, $endAt);
                $this->shiftWriteRepository->save($coordinatorShift);
                $coordinatorNeedsShift = true;
            }

            $writeActivity->update(
                $activity->getRoomId(),
                $activity->getActivityTypeId(),
                $coordinatorUserId,
                $activity->getStartAt(),
                $activity->getEndAt()
            );
            $this->roomActivityWriteRepository->save($writeActivity);
            ++$reassigned;
        }

        return [$reassigned, $skipped];
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
