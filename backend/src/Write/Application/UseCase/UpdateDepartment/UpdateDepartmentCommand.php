<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateDepartment;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateDepartmentCommand
{
    public function __construct(
        public readonly string $id,

        #[Assert\NotBlank(message: 'validation.department.name.not_blank')]
        #[Assert\Length(max: 255, maxMessage: 'validation.department.name_too_long')]
        public readonly string $name,

        #[Assert\NotBlank(message: 'validation.company_id.not_blank')]
        public readonly string $companyId,

        public readonly ?string $description
    ) {}
}
