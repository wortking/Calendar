<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeactivateUser;

use Symfony\Component\Validator\Constraints as Assert;

class DeactivateUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId
    ) {}
}
