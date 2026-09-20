<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ChangePassword;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.current_password.not_blank')]
        public string $currentPassword,

        #[Assert\NotBlank(message: 'validation.new_password.not_blank')]
        #[Assert\Length(min: 8, minMessage: 'validation.new_password.min_length')]
        #[Assert\Regex(pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', message: 'validation.new_password.weak')]
        public string $newPassword
    ) {}
}
