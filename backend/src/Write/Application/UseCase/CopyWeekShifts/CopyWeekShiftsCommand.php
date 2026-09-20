<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CopyWeekShifts;

use Symfony\Component\Validator\Constraints as Assert;

class CopyWeekShiftsCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.week_start.not_blank')]
        public string $weekStart,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId,

        public bool $includeRoomActivities = false
    ) {}
}
