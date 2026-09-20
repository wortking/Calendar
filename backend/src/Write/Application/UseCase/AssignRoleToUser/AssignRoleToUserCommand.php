<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignRoleToUser;

use Symfony\Component\Validator\Constraints as Assert;

class AssignRoleToUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.role.name.not_blank')]
        public string $roleName,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId
    ) {}
}
