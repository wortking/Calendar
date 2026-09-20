<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRoomActivity;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRoomActivityCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.room_id.not_blank')]
        public string $roomId,

        #[Assert\NotBlank(message: 'validation.activity_type_id.not_blank')]
        public string $activityTypeId,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.shift.start_at.not_blank')]
        public string $startAt,

        #[Assert\NotBlank(message: 'validation.shift.end_at.not_blank')]
        public string $endAt
    ) {}
}
