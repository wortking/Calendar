<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetRole;

class GetRoleResponse
{
    /**
     * @param string[] $permissionIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $permissionIds
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissionIds' => $this->permissionIds,
        ];
    }
}
