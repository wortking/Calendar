<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ActivateUserImage;

use Symfony\Component\Validator\Constraints as Assert;

class ActivateUserImageCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.image_id.not_blank')]
        public string $imageId
    ) {}
}
