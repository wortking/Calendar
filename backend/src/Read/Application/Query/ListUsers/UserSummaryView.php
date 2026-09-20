<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUsers;

class UserSummaryView
{
    /**
     * @param string[] $roles
     * @param array<int, array{id: string, name: string}> $departments
     * @param array{id: string, name: string}|null $company
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly array $roles,
        public readonly array $departments,
        public readonly ?array $company,
        public readonly ?string $deactivatedAt
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'roles' => $this->roles,
            'departments' => $this->departments,
            'company' => $this->company,
            'deactivatedAt' => $this->deactivatedAt,
        ];
    }
}
