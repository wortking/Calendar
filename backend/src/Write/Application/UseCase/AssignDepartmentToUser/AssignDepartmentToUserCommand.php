<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignDepartmentToUser;

use Symfony\Component\Validator\Constraints as Assert;

class AssignDepartmentToUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.department_id.not_blank')]
        public string $departmentId,

        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $actingUserId
    ) {}
}
