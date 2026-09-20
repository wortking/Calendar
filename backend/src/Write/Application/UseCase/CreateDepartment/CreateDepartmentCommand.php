<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateDepartment;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDepartmentCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.department.name.not_blank')]
        #[Assert\Length(max: 255, maxMessage: 'validation.department.name_too_long')]
        public string $name,

        #[Assert\NotBlank(message: 'validation.company_id.not_blank')]
        public string $companyId,

        public ?string $description = null
    ) {}
}
