<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteRoomActivity;

use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteRoomActivityCommandHandler
{
    public function __construct(
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository
    ) {}

    public function __invoke(DeleteRoomActivityCommand $command): DeleteRoomActivityResponse
    {
        $roomActivity = $this->roomActivityWriteRepository->findById($command->id);

        if (null === $roomActivity) {
            throw new TranslatableException('handler.room_activity.not_found', ['%id%' => $command->id]);
        }

        $this->roomActivityWriteRepository->delete($roomActivity);

        return new DeleteRoomActivityResponse($command->id);
    }
}
