<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteDepartment;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteDepartmentCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.department_id.not_blank')]
        public string $id
    ) {}
}
