<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AssignPermissionToRole;

use Symfony\Component\Validator\Constraints as Assert;

class AssignPermissionToRoleCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.role_id.not_blank')]
        public string $roleId,

        #[Assert\NotBlank(message: 'validation.permission_id.not_blank')]
        public string $permissionId
    ) {}
}
