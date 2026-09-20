<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRoomActivity;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Read\Domain\Repository\ShiftReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\RoomActivity;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateRoomActivityCommandHandler
{
    public function __construct(
        private RoomReadRepositoryInterface $roomRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeRepository,
        private DepartmentReadRepositoryInterface $departmentRepository,
        private UserReadRepositoryInterface $userRepository,
        private ShiftReadRepositoryInterface $shiftRepository,
        private RoomActivityReadRepositoryInterface $roomActivityReadRepository,
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository
    ) {}

    public function __invoke(CreateRoomActivityCommand $command): CreateRoomActivityResponse
    {
        $room = $this->roomRepository->findById($command->roomId);
        if (null === $room) {
            throw new TranslatableException('handler.room.not_found', ['%id%' => $command->roomId]);
        }

        $this->assertActivityTypeBelongsToCompany($command->activityTypeId, $room->getCompanyId());

        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        $startAt = $this->parseDateTime($command->startAt);
        $endAt = $this->parseDateTime($command->endAt);

        $this->assertUserHasShiftCovering($command->userId, $startAt, $endAt);
        $this->assertRoomAvailable($command->roomId, $startAt, $endAt, $command->activityTypeId, null);
        $this->assertUserAvailable($command->userId, $startAt, $endAt, null);

        $id = Uuid::v7()->toRfc4122();
        $roomActivity = new RoomActivity($id, $command->roomId, $command->activityTypeId, $command->userId, $startAt, $endAt);

        $this->roomActivityWriteRepository->save($roomActivity);

        return new CreateRoomActivityResponse(
            $roomActivity->getId(),
            $roomActivity->getRoomId(),
            $roomActivity->getActivityTypeId(),
            $roomActivity->getUserId(),
            $roomActivity->getStartAt()->format('Y-m-d\TH:i:s'),
            $roomActivity->getEndAt()->format('Y-m-d\TH:i:s')
        );
    }

    private function parseDateTime(string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new TranslatableException('domain.shift.invalid_date');
        }
    }

    private function assertActivityTypeBelongsToCompany(string $activityTypeId, string $companyId): void
    {
        $activityType = $this->activityTypeRepository->findById($activityTypeId);
        if (null === $activityType) {
            throw new TranslatableException('handler.activity_type.not_found', ['%id%' => $activityTypeId]);
        }

        $department = $this->departmentRepository->findById($activityType->getDepartmentId());
        if (null === $department || $department->getCompanyId() !== $companyId) {
            throw new TranslatableException('handler.activity_type.company_mismatch');
        }
    }

    private function assertUserHasShiftCovering(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): void
    {
        foreach ($this->shiftRepository->findCoveringRange($startAt, $endAt) as $shift) {
            if ($shift->getUserId() === $userId) {
                return;
            }
        }

        throw new TranslatableException('handler.room_activity.user_without_shift');
    }

    /**
     * Una sala no puede tener OTRA actividad al mismo horario; la misma
     * actividad sí puede repetirse (varios empleados haciendo lo mismo a
     * la vez, ej. "Sala" como piso general, o dos instructores de una
     * misma clase).
     */
    private function assertRoomAvailable(string $roomId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, string $activityTypeId, ?string $excludeId): void
    {
        foreach ($this->roomActivityReadRepository->findByRoomInRange($roomId, $startAt, $endAt) as $conflict) {
            if ($conflict->getId() === $excludeId || $conflict->getActivityTypeId() === $activityTypeId) {
                continue;
            }

            throw new TranslatableException('handler.room_activity.room_conflict');
        }
    }

    private function assertUserAvailable(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, ?string $excludeId): void
    {
        foreach ($this->roomActivityReadRepository->findByUserIdInRange($userId, $startAt, $endAt) as $conflict) {
            if ($conflict->getId() !== $excludeId) {
                throw new TranslatableException('handler.room_activity.user_conflict');
            }
        }
    }
}
