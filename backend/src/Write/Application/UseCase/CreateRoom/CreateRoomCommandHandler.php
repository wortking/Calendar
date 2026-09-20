<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRoom;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\CompanyReadRepositoryInterface;
use App\Read\Domain\Repository\RoomReadRepositoryInterface;
use App\Write\Domain\Model\Room;
use App\Write\Domain\Repository\RoomWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateRoomCommandHandler
{
    public function __construct(
        private CompanyReadRepositoryInterface $companyReadRepository,
        private RoomReadRepositoryInterface $roomReadRepository,
        private RoomWriteRepositoryInterface $roomWriteRepository
    ) {}

    public function __invoke(CreateRoomCommand $command): CreateRoomResponse
    {
        if (null === $this->companyReadRepository->findById($command->companyId)) {
            throw new TranslatableException('handler.company.not_found', ['%id%' => $command->companyId]);
        }

        if (null !== $this->roomReadRepository->findByCompanyIdAndName($command->companyId, $command->name)) {
            throw new TranslatableException('handler.room.name_taken', ['%name%' => $command->name]);
        }

        $id = Uuid::v7()->toRfc4122();
        $room = new Room($id, $command->companyId, $command->name);

        $this->roomWriteRepository->save($room);

        return new CreateRoomResponse($room->getId(), $room->getCompanyId(), $room->getName());
    }
}
