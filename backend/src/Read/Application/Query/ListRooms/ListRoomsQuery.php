<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRooms;

class ListRoomsQuery
{
    public function __construct(
        public readonly ?string $companyId = null
    ) {}
}
