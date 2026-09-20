<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRooms;

use App\Read\Domain\Repository\RoomReadRepositoryInterface;

class ListRoomsQueryHandler
{
    public function __construct(
        private RoomReadRepositoryInterface $roomRepository
    ) {}

    public function __invoke(ListRoomsQuery $query): ListRoomsResponse
    {
        $entities = null !== $query->companyId
            ? $this->roomRepository->findByCompanyId($query->companyId)
            : $this->roomRepository->findAll();

        $rooms = array_map(
            static fn ($room) => new RoomView($room->getId(), $room->getCompanyId(), $room->getName()),
            $entities
        );

        return new ListRoomsResponse($rooms);
    }
}
