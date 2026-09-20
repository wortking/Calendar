<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateActivityType;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Read\Domain\Repository\DepartmentReadRepositoryInterface;
use App\Write\Domain\Model\ActivityType;
use App\Write\Domain\Repository\ActivityTypeWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateActivityTypeCommandHandler
{
    public function __construct(
        private DepartmentReadRepositoryInterface $departmentReadRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeReadRepository,
        private ActivityTypeWriteRepositoryInterface $activityTypeWriteRepository
    ) {}

    public function __invoke(CreateActivityTypeCommand $command): CreateActivityTypeResponse
    {
        if (null === $this->departmentReadRepository->findById($command->departmentId)) {
            throw new TranslatableException('handler.department.not_found', ['%id%' => $command->departmentId]);
        }

        if (null !== $this->activityTypeReadRepository->findByDepartmentIdAndName($command->departmentId, $command->name)) {
            throw new TranslatableException('handler.activity_type.name_taken', ['%name%' => $command->name]);
        }

        $id = Uuid::v7()->toRfc4122();
        $activityType = new ActivityType($id, $command->departmentId, $command->name, $command->color);

        $this->activityTypeWriteRepository->save($activityType);

        return new CreateActivityTypeResponse(
            $activityType->getId(),
            $activityType->getDepartmentId(),
            $activityType->getName(),
            $activityType->getColor()
        );
    }
}
