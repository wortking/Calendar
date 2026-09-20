<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteRoomActivity;

class DeleteRoomActivityResponse
{
    public function __construct(
        public readonly string $id
    ) {}
}
