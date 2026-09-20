<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListEligibleUsersForRoomActivity;

use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;

/**
 * Empleados que tienen un turno que cubre exactamente el horario dado, y
 * que no están ya agendados en OTRA actividad de sala que se solape con
 * ese mismo horario (no se puede estar en dos actividades a la vez).
 */
class ListEligibleUsersForRoomActivityQueryHandler
{
    public function __construct(
        private ShiftReadRepositoryInterface $shiftRepository,
        private RoomActivityReadRepositoryInterface $roomActivityRepository,
        private UserReadRepositoryInterface $userRepository
    ) {}

    public function __invoke(ListEligibleUsersForRoomActivityQuery $query): ListEligibleUsersForRoomActivityResponse
    {
        $userIds = array_values(array_unique(array_map(
            static fn ($shift) => $shift->getUserId(),
            $this->shiftRepository->findCoveringRange($query->startAt, $query->endAt)
        )));

        $views = [];

        foreach ($userIds as $userId) {
            if ($this->isAlreadyBusy($userId, $query->startAt, $query->endAt, $query->excludeRoomActivityId)) {
                continue;
            }

            $user = $this->userRepository->findById($userId);
            if (null === $user) {
                continue;
            }

            $userName = trim(sprintf('%s %s', $user->getFirstName() ?? '', $user->getLastName() ?? ''));

            $views[] = new EligibleUserView($userId, $user->getEmail(), '' !== $userName ? $userName : null);
        }

        usort($views, static fn (EligibleUserView $a, EligibleUserView $b) => $a->email <=> $b->email);

        return new ListEligibleUsersForRoomActivityResponse($views);
    }

    private function isAlreadyBusy(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, ?string $excludeRoomActivityId): bool
    {
        foreach ($this->roomActivityRepository->findByUserIdInRange($userId, $startAt, $endAt) as $conflict) {
            if ($conflict->getId() !== $excludeRoomActivityId) {
                return true;
            }
        }

        return false;
    }
}
