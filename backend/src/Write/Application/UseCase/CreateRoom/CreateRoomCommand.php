<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRoom;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRoomCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.company_id.not_blank')]
        public string $companyId,

        #[Assert\NotBlank(message: 'validation.room.name.not_blank')]
        public string $name
    ) {}
}
