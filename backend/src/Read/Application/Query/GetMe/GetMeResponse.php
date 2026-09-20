<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetMe;

class GetMeResponse
{
    /**
     * @param string[] $roles
     * @param array<int, array{id: string, name: string}> $departments
     * @param array{id: string, name: string, openingTime: string, closingTime: string}|null $company
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly array $roles,
        public readonly ?string $dni,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $sex,
        public readonly ?\DateTimeImmutable $lastLoginAt,
        public readonly ?\DateTimeImmutable $emailVerifiedAt,
        public readonly ?string $avatarUrl,
        public readonly array $departments,
        public readonly ?array $company,
        public readonly bool $mustChangePassword
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'dni' => $this->dni,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'sex' => $this->sex,
            'lastLoginAt' => $this->lastLoginAt?->format(DATE_ATOM),
            'emailVerifiedAt' => $this->emailVerifiedAt?->format(DATE_ATOM),
            'avatarUrl' => $this->avatarUrl,
            'departments' => $this->departments,
            'company' => $this->company,
            'mustChangePassword' => $this->mustChangePassword,
        ];
    }
}
