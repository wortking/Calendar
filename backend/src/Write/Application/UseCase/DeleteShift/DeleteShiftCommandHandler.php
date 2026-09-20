<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\DeleteShift;

use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteShiftCommandHandler
{
    public function __construct(
        private ShiftWriteRepositoryInterface $shiftWriteRepository,
        private DepartmentScopeGuard $scopeGuard
    ) {}

    public function __invoke(DeleteShiftCommand $command): DeleteShiftResponse
    {
        $shift = $this->shiftWriteRepository->findById($command->shiftId);

        if (null === $shift || $shift->getUserId() !== $command->userId) {
            throw new TranslatableException('handler.shift.not_found', ['%id%' => $command->shiftId]);
        }

        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->userId);

        $this->shiftWriteRepository->delete($shift);

        return new DeleteShiftResponse($command->shiftId);
    }
}
