<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class EmailVerificationToken
{
    private Uuid $id;
    private Uuid $userId;
    private string $tokenHash;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $usedAt;

    public function __construct(string $id, string $userId, string $tokenHash, \DateTimeImmutable $expiresAt)
    {
        if (empty($tokenHash)) {
            throw new TranslatableException('domain.email_verification_token.token_hash_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->userId = Uuid::fromString($userId);
        $this->tokenHash = $tokenHash;
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
