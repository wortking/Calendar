<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoomActivities;

use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;

class ListRoomActivitiesQueryHandler
{
    public function __construct(
        private RoomActivityReadRepositoryInterface $roomActivityRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeRepository,
        private UserReadRepositoryInterface $userRepository
    ) {}

    public function __invoke(ListRoomActivitiesQuery $query): ListRoomActivitiesResponse
    {
        $views = [];

        foreach ($this->roomActivityRepository->findByRoomInRange($query->roomId, $query->from, $query->to) as $roomActivity) {
            $user = $this->userRepository->findById($roomActivity->getUserId());

            if (null === $user) {
                continue;
            }

            $activityType = $this->activityTypeRepository->findById($roomActivity->getActivityTypeId());
            $userName = trim(sprintf('%s %s', $user->getFirstName() ?? '', $user->getLastName() ?? ''));

            $views[] = new RoomActivityView(
                $roomActivity->getId(),
                $roomActivity->getRoomId(),
                $roomActivity->getActivityTypeId(),
                $activityType?->getName(),
                $activityType?->getColor(),
                $roomActivity->getUserId(),
                $user->getEmail(),
                '' !== $userName ? $userName : null,
                $roomActivity->getStartAt()->format('Y-m-d\TH:i:s'),
                $roomActivity->getEndAt()->format('Y-m-d\TH:i:s')
            );
        }

        return new ListRoomActivitiesResponse($views);
    }
}
