<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateRoom;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Write\Domain\Repository\RoomWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateRoomCommandHandler
{
    public function __construct(
        private RoomReadRepositoryInterface $roomReadRepository,
        private RoomWriteRepositoryInterface $roomWriteRepository
    ) {}

    public function __invoke(UpdateRoomCommand $command): UpdateRoomResponse
    {
        $room = $this->roomWriteRepository->findById($command->id);

        if (null === $room) {
            throw new TranslatableException('handler.room.not_found', ['%id%' => $command->id]);
        }

        if ($command->name !== $room->getName()) {
            $existing = $this->roomReadRepository->findByCompanyIdAndName($room->getCompanyId(), $command->name);
            if (null !== $existing && $existing->getId() !== $room->getId()) {
                throw new TranslatableException('handler.room.name_taken', ['%name%' => $command->name]);
            }

            $room->rename($command->name);
        }

        $this->roomWriteRepository->save($room);

        return new UpdateRoomResponse($room->getId(), $room->getCompanyId(), $room->getName());
    }
}
