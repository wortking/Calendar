<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class UserImage
{
    private Uuid $id;
    private Uuid $userId;
    private string $url;
    private bool $isActive;
    private \DateTimeImmutable $uploadedAt;

    public function __construct(string $id, string $userId, string $url, \DateTimeImmutable $uploadedAt)
    {
        if (empty($url)) {
            throw new TranslatableException('domain.user_image.url_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->userId = Uuid::fromString($userId);
        $this->url = $url;
        $this->isActive = false;
        $this->uploadedAt = $uploadedAt;
    }

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

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
