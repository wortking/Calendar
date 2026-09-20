<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RegisterUser;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.email.not_blank')]
        #[Assert\Email(message: 'validation.email.invalid')]
        public string $email
    ) {}
}
