<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteRoom;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteRoomCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.room_id.not_blank')]
        public string $id
    ) {}
}
