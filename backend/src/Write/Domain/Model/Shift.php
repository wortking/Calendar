<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class Shift
{
    private Uuid $id;
    private Uuid $userId;
    private \DateTimeImmutable $startAt;
    private \DateTimeImmutable $endAt;

    public function __construct(string $id, string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt)
    {
        if ($endAt <= $startAt) {
            throw new TranslatableException('domain.shift.invalid_range');
        }

        $this->id = Uuid::fromString($id);
        $this->userId = Uuid::fromString($userId);
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getUserId(): string
    {
        return $this->userId->toRfc4122();
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function reschedule(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): void
    {
        if ($endAt <= $startAt) {
            throw new TranslatableException('domain.shift.invalid_range');
        }

        $this->userId = Uuid::fromString($userId);
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }
}
