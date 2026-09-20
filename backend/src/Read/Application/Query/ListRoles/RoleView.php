<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListRoles;

class RoleView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
