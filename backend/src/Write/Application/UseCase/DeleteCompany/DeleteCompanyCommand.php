<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteCompany;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteCompanyCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.company_id.not_blank')]
        public string $id
    ) {}
}
