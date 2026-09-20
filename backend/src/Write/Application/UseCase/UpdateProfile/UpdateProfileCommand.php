<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateProfile;

use App\Write\Domain\Model\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\Length(max: 255, maxMessage: 'validation.profile.first_name_too_long')]
        public ?string $firstName,

        #[Assert\Length(max: 255, maxMessage: 'validation.profile.last_name_too_long')]
        public ?string $lastName,

        #[Assert\Length(max: 20, maxMessage: 'validation.profile.dni_too_long')]
        public ?string $dni,

        #[Assert\Choice(choices: User::SEX_OPTIONS, message: 'validation.profile.sex_invalid')]
        public ?string $sex
    ) {}
}
