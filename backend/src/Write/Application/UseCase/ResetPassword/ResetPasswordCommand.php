<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResetPassword;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.email.not_blank')]
        public string $email,

        #[Assert\NotBlank(message: 'validation.code.not_blank')]
        public string $code,

        #[Assert\NotBlank(message: 'validation.new_password.not_blank')]
        #[Assert\Length(min: 8, minMessage: 'validation.new_password.min_length')]
        #[Assert\Regex(pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', message: 'validation.new_password.weak')]
        public string $newPassword
    ) {}
}
