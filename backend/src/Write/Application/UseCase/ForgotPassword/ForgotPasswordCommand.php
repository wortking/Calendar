<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ForgotPassword;

use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.email.not_blank')]
        #[Assert\Email(message: 'validation.email.invalid')]
        public string $email
    ) {}
}
