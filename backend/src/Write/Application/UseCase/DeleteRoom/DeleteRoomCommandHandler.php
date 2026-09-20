<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteRoom;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoomActivityReadRepositoryInterface;
use App\Write\Domain\Repository\RoomActivityWriteRepositoryInterface;
use App\Write\Domain\Repository\RoomWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Borrar una sala borra en cascada las actividades puntuales agendadas en
 * ella (su calendario propio deja de existir junto con la sala).
 */
#[AsMessageHandler]
class DeleteRoomCommandHandler
{
    public function __construct(
        private RoomWriteRepositoryInterface $roomWriteRepository,
        private RoomActivityReadRepositoryInterface $roomActivityReadRepository,
        private RoomActivityWriteRepositoryInterface $roomActivityWriteRepository
    ) {}

    public function __invoke(DeleteRoomCommand $command): DeleteRoomResponse
    {
        $room = $this->roomWriteRepository->findById($command->id);

        if (null === $room) {
            throw new TranslatableException('handler.room.not_found', ['%id%' => $command->id]);
        }

        foreach ($this->roomActivityReadRepository->findByRoomId($command->id) as $roomActivity) {
            $writeRoomActivity = $this->roomActivityWriteRepository->findById($roomActivity->getId());

            if (null !== $writeRoomActivity) {
                $this->roomActivityWriteRepository->delete($writeRoomActivity);
            }
        }

        $this->roomWriteRepository->delete($room);

        return new DeleteRoomResponse($command->id);
    }
}
