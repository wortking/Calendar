<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateActivityType;

use Symfony\Component\Validator\Constraints as Assert;

class CreateActivityTypeCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.department_id.not_blank')]
        public string $departmentId,

        #[Assert\NotBlank(message: 'validation.activity_type.name.not_blank')]
        public string $name,

        public ?string $color = null
    ) {}
}
