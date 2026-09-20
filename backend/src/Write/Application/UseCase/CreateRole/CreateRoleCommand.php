<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRole;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRoleCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.role.name.not_blank')]
        #[Assert\Regex(pattern: '/^ROLE_[A-Z_]+$/', message: 'validation.role.name.invalid_format')]
        public string $name,

        public ?string $description = null
    ) {}
}
