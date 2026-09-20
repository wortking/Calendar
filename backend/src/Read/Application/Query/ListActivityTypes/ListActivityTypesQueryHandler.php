<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListActivityTypes;

use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;

class ListActivityTypesQueryHandler
{
    public function __construct(
        private ActivityTypeReadRepositoryInterface $activityTypeRepository
    ) {}

    public function __invoke(ListActivityTypesQuery $query): ListActivityTypesResponse
    {
        $entities = null !== $query->departmentIds
            ? $this->activityTypeRepository->findByDepartmentIds($query->departmentIds)
            : $this->activityTypeRepository->findAll();

        $activityTypes = array_map(
            static fn ($activityType) => new ActivityTypeView(
                $activityType->getId(),
                $activityType->getDepartmentId(),
                $activityType->getName(),
                $activityType->getColor()
            ),
            $entities
        );

        return new ListActivityTypesResponse($activityTypes);
    }
}
