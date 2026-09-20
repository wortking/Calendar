<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateShift;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateShiftCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.shift_id.not_blank')]
        public string $shiftId,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.shift.start_at.not_blank')]
        public string $startAt,

        #[Assert\NotBlank(message: 'validation.shift.end_at.not_blank')]
        public string $endAt,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId
    ) {}
}
