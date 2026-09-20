<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class ActivityType
{
    private Uuid $id;
    private Uuid $departmentId;
    private string $name;
    private ?string $color;

    public function __construct(string $id, string $departmentId, string $name, ?string $color = null)
    {
        if (empty($name)) {
            throw new TranslatableException('domain.activity_type.name_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->departmentId = Uuid::fromString($departmentId);
        $this->name = $name;
        $this->color = $color;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function update(string $name, ?string $color): void
    {
        if (empty($name)) {
            throw new TranslatableException('domain.activity_type.name_blank');
        }

        $this->name = $name;
        $this->color = $color;
    }
}
