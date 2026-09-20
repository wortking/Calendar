<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListEligibleUsersForRoomActivity;

class EligibleUserView
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $name
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
