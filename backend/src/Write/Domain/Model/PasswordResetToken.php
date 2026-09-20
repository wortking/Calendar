<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class PasswordResetToken
{
    private Uuid $id;
    private Uuid $userId;
    private string $codeHash;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $usedAt;

    public function __construct(string $id, string $userId, string $codeHash, \DateTimeImmutable $expiresAt)
    {
        if (empty($codeHash)) {
            throw new TranslatableException('domain.password_reset_token.code_hash_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->userId = Uuid::fromString($userId);
        $this->codeHash = $codeHash;
        $this->expiresAt = $expiresAt;
        $this->usedAt = null;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function markAsUsed(): void
    {
        $this->usedAt = new \DateTimeImmutable();
    }
}
