<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateCompany;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCompanyCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.company.name.not_blank')]
        public string $name,

        #[Assert\NotBlank(message: 'validation.company.opening_time.not_blank')]
        public string $openingTime,

        #[Assert\NotBlank(message: 'validation.company.closing_time.not_blank')]
        public string $closingTime
    ) {}
}
