<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RegisterUser;

class RegisterUserResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $email
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}
