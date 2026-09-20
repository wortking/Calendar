<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class Room
{
    private Uuid $id;
    private Uuid $companyId;
    private string $name;

    public function __construct(string $id, string $companyId, string $name)
    {
        if (empty($name)) {
            throw new TranslatableException('domain.room.name_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->companyId = Uuid::fromString($companyId);
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getCompanyId(): string
    {
        return $this->companyId->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        if (empty($name)) {
            throw new TranslatableException('domain.room.name_blank');
        }

        $this->name = $name;
    }
}
