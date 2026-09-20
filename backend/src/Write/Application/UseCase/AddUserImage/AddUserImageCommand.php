<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AddUserImage;

use Symfony\Component\Validator\Constraints as Assert;

class AddUserImageCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.user_id.not_blank')]
        public string $userId,

        #[Assert\NotBlank(message: 'validation.user_image.url.not_blank')]
        public string $url
    ) {}
}
