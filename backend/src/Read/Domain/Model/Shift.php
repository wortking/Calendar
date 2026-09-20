<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class Shift
{
    private Uuid $id;
    private Uuid $userId;
    private \DateTimeImmutable $startAt;
    private \DateTimeImmutable $endAt;

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
}
