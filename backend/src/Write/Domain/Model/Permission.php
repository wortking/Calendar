<?php

declare(strict_types=1);

namespace App\Write\Domain\Model;

use App\Shared\Domain\Exception\TranslatableException;
use Symfony\Component\Uid\Uuid;

class Permission
{
    private Uuid $id;
    private string $name;
    private ?string $description;

    public function __construct(string $id, string $name, ?string $description = null)
    {
        if (empty($name)) {
            throw new TranslatableException('domain.permission.name_blank');
        }

        $this->id = Uuid::fromString($id);
        $this->name = $name;
        $this->description = $description;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
