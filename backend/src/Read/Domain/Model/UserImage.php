<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class UserImage
{
    private Uuid $id;
    private Uuid $userId;
    private string $url;
    private bool $isActive;
    private \DateTimeImmutable $uploadedAt;

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
