<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

class User implements PasswordAuthenticatedUserInterface
{
    public const SEX_OPTIONS = ['male', 'female', 'other'];

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
    private ?string $deactivatedBy;
    private bool $mustChangePassword;

    public function __construct(
        string $id,
        string $email,
        string $password,
        ?string $dni = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $sex = null
    ) {
        if (empty($email)) {
            throw new TranslatableException('domain.user.email_blank');
        }

        if (empty($password)) {
            throw new TranslatableException('domain.user.password_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->email = $email;
        $this->password = $password;
        $this->dni = $dni;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->sex = $sex;
        $this->lastLoginAt = null;
        $this->emailVerifiedAt = null;
        $this->deactivatedAt = null;
        $this->deactivatedBy = null;
        $this->mustChangePassword = false;
    }

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

    // Cualquier cambio de contraseña hecho por el propio usuario (a mano o
    // recuperándola por "olvidé mi contraseña") cuenta como haber dejado
    // atrás la contraseña temporal, así que limpia el flag de turno.
    public function changePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
        $this->mustChangePassword = false;
    }

    public function getMustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function requirePasswordChange(): void
    {
        $this->mustChangePassword = true;
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

    public function recordLogin(\DateTimeImmutable $at): void
    {
        $this->lastLoginAt = $at;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function verifyEmail(\DateTimeImmutable $at): void
    {
        $this->emailVerifiedAt = $at;
    }

    public function updateProfile(?string $firstName, ?string $lastName, ?string $dni, ?string $sex): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->dni = $dni;
        $this->sex = $sex;
    }

    public function getDeactivatedAt(): ?\DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function deactivate(\DateTimeImmutable $at, string $byUserId): void
    {
        $this->deactivatedAt = $at;
        $this->deactivatedBy = $byUserId;
    }
}
