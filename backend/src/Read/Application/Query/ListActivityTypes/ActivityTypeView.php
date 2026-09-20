<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListActivityTypes;

class ActivityTypeView
{
    public function __construct(
        public readonly string $id,
        public readonly string $departmentId,
        public readonly string $name,
        public readonly ?string $color
    ) {}

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'departmentId' => $this->departmentId,
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}
