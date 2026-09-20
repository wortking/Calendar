<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListMyShifts;

use App\Read\Application\Query\RoomActivityOfShiftView;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;

class ListMyShiftsQueryHandler
{
    public function __construct(
        private ShiftReadRepositoryInterface $shiftRepository,
        private RoomActivityReadRepositoryInterface $roomActivityRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeRepository,
        private RoomReadRepositoryInterface $roomRepository
    ) {}

    public function __invoke(ListMyShiftsQuery $query): ListMyShiftsResponse
    {
        $shifts = array_map(
            fn ($shift) => new ShiftView(
                $shift->getId(),
                $shift->getStartAt()->format('Y-m-d\TH:i:s'),
                $shift->getEndAt()->format('Y-m-d\TH:i:s'),
                $this->resolveRoomActivities($shift->getUserId(), $shift->getStartAt(), $shift->getEndAt())
            ),
            $this->shiftRepository->findByUserIdInRange($query->userId, $query->from, $query->to)
        );

        return new ListMyShiftsResponse($shifts);
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
