<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListActivityTypes;

class ListActivityTypesResponse
{
    /**
     * @param ActivityTypeView[] $activityTypes
     */
    public function __construct(
        public readonly array $activityTypes
    ) {}

    public function serialize(): array
    {
        return array_map(static fn (ActivityTypeView $activityType) => $activityType->serialize(), $this->activityTypes);
    }
}
