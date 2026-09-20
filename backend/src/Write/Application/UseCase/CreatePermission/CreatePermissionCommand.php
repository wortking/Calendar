<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreatePermission;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePermissionCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.permission.name.not_blank')]
        #[Assert\Regex(pattern: '/^[a-z][a-z_]*\.[a-z][a-z_]*$/', message: 'validation.permission.name.invalid_format')]
        public string $name,

        public ?string $description = null
    ) {}
}
