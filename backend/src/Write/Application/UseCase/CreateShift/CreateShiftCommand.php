<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateShift;

use Symfony\Component\Validator\Constraints as Assert;

class CreateShiftCommand
{
    public function __construct(
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
