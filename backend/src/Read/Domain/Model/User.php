<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private Uuid $id;
    private string $email;
    private string $password;
    private ?string $dni;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $sex;
    private ?\DateTimeImmutable $lastLoginAt;
    private ?\DateTimeImmutable $emailVerifiedAt;
    private ?\DateTimeImmutable $deactivatedAt;
    private bool $mustChangePassword = false;

    /**
     * Cargados aparte por el repositorio (no son columnas de "users"),
     * vía join con user_roles/roles y role_permissions/permissions.
     *
     * @var string[]
     */
    private array $roleNames = [];

    /** @var string[] */
    private array $permissionNames = [];

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getDeactivatedAt(): ?\DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function isActive(): bool
    {
        return null === $this->deactivatedAt;
    }

    public function getMustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    /**
     * @param string[] $roleNames
     */
    public function setRoleNames(array $roleNames): void
    {
        $this->roleNames = $roleNames;
    }

    /**
     * @param string[] $permissionNames
     */
    public function setPermissionNames(array $permissionNames): void
    {
        $this->permissionNames = $permissionNames;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roleNames;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionNames, true);
    }

    public function eraseCredentials(): void
    {
    }
}
