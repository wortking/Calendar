<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateActivityType;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateActivityTypeCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.activity_type_id.not_blank')]
        public string $id,

        #[Assert\NotBlank(message: 'validation.activity_type.name.not_blank')]
        public string $name,

        public ?string $color = null
    ) {}
}
