<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateActivityType;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\ActivityTypeReadRepositoryInterface;
use App\Write\Domain\Repository\ActivityTypeWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateActivityTypeCommandHandler
{
    public function __construct(
        private ActivityTypeWriteRepositoryInterface $activityTypeWriteRepository,
        private ActivityTypeReadRepositoryInterface $activityTypeReadRepository
    ) {}

    public function __invoke(UpdateActivityTypeCommand $command): UpdateActivityTypeResponse
    {
        $activityType = $this->activityTypeWriteRepository->findById($command->id);

        if (null === $activityType) {
            throw new TranslatableException('handler.activity_type.not_found', ['%id%' => $command->id]);
        }

        if ($command->name !== $activityType->getName()) {
            $existing = $this->activityTypeReadRepository->findByDepartmentIdAndName($activityType->getDepartmentId(), $command->name);
            if (null !== $existing && $existing->getId() !== $activityType->getId()) {
                throw new TranslatableException('handler.activity_type.name_taken', ['%name%' => $command->name]);
            }
        }

        $activityType->update($command->name, $command->color);
        $this->activityTypeWriteRepository->save($activityType);

        return new UpdateActivityTypeResponse(
            $activityType->getId(),
            $activityType->getDepartmentId(),
            $activityType->getName(),
            $activityType->getColor()
        );
    }
}
