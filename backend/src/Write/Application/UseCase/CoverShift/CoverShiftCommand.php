<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CoverShift;

use Symfony\Component\Validator\Constraints as Assert;

class CoverShiftCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.shift_id.not_blank')]
        public string $shiftId,

        #[Assert\NotBlank(message: 'validation.shift.end_at.not_blank')]
        public string $cutoffAt,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $replacementUserId,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId
    ) {}
}
