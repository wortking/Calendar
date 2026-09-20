<?php

declare(strict_types=1);

namespace App\Read\Domain\Model;

use Symfony\Component\Uid\Uuid;

class ActivityType
{
    private Uuid $id;
    private Uuid $departmentId;
    private string $name;
    private ?string $color;

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
}
