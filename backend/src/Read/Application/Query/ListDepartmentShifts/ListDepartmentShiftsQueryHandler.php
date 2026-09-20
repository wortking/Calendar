<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListDepartmentShifts;

use App\Read\Application\Query\ListShiftsByDate\ShiftWithUserView;
use App\Read\Application\Query\RoomActivityOfShiftView;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use App\Read\Domain\Repository\UserImageReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;

/**
 * Turnos de todos los compañeros que comparten al menos un departamento con
 * el usuario dado (incluido él mismo). Si el usuario no tiene ningún
 * departamento asignado, cae a mostrar solo sus propios turnos.
 */
class ListDepartmentShiftsQueryHandler
{
    public function __construct(
        private DepartmentAssignmentReadRepositoryInterface $departmentAssignmentRepository,
        private ShiftReadRepositoryInterface $shiftRepository,
        private UserReadRepositoryInterface $userRepository,
        private RoomActivityReadRepositoryInterface $roomActivityRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeRepository,
        private RoomReadRepositoryInterface $roomRepository,
        private UserImageReadRepositoryInterface $userImageRepository
    ) {}

    public function __invoke(ListDepartmentShiftsQuery $query): ListDepartmentShiftsResponse
    {
        $departmentIds = array_map(
            static fn ($department) => $department->getId(),
            $this->departmentAssignmentRepository->findDepartmentsByUserId($query->userId)
        );

        $userIds = [] !== $departmentIds
            ? $this->departmentAssignmentRepository->findUserIdsByDepartmentIds($departmentIds)
            : [];

        $userIds = array_values(array_unique([...$userIds, $query->userId]));

        $views = [];

        foreach ($this->shiftRepository->findByUserIdsInRange($userIds, $query->from, $query->to) as $shift) {
            $user = $this->userRepository->findById($shift->getUserId());

            if (null === $user) {
                continue;
            }

            $userName = trim(sprintf('%s %s', $user->getFirstName() ?? '', $user->getLastName() ?? ''));

            $views[] = new ShiftWithUserView(
                $shift->getId(),
                $shift->getUserId(),
                $user->getEmail(),
                '' !== $userName ? $userName : null,
                $shift->getStartAt()->format('Y-m-d\TH:i:s'),
                $shift->getEndAt()->format('Y-m-d\TH:i:s'),
                $this->resolveAvatarUrl($shift->getUserId()),
                $this->resolveRoomActivities($shift->getUserId(), $shift->getStartAt(), $shift->getEndAt())
            );
        }

        return new ListDepartmentShiftsResponse($views);
    }

    private function resolveAvatarUrl(string $userId): ?string
    {
        $activeImage = array_values(array_filter(
            $this->userImageRepository->findByUserId($userId),
            static fn ($image) => $image->isActive()
        ))[0] ?? null;

        return $activeImage?->getUrl();
    }

    /**
     * @return RoomActivityOfShiftView[]
     */
    private function resolveRoomActivities(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): array
    {
        return array_map(
            function ($roomActivity) {
                $activityType = $this->activityTypeRepository->findById($roomActivity->getActivityTypeId());
                $room = $this->roomRepository->findById($roomActivity->getRoomId());

                return new RoomActivityOfShiftView(
                    $roomActivity->getId(),
                    $roomActivity->getRoomId(),
                    $room?->getName(),
                    $roomActivity->getActivityTypeId(),
                    $activityType?->getName(),
                    $activityType?->getColor(),
                    $roomActivity->getStartAt()->format('Y-m-d\TH:i:s'),
                    $roomActivity->getEndAt()->format('Y-m-d\TH:i:s')
                );
            },
            $this->roomActivityRepository->findByUserIdInRange($userId, $startAt, $endAt)
        );
    }
}
