<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateRoom;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateRoomCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.room_id.not_blank')]
        public string $id,

        #[Assert\NotBlank(message: 'validation.room.name.not_blank')]
        public string $name
    ) {}
}
