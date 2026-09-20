<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateShift;

use App\Shared\Application\Security\CompanyHoursResolver;
use App\Shared\Application\Security\DepartmentScopeGuard;
use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\ShiftWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateShiftCommandHandler
{
    public function __construct(
        private ShiftWriteRepositoryInterface $shiftWriteRepository,
        private UserReadRepositoryInterface $userRepository,
        private DepartmentScopeGuard $scopeGuard,
        private CompanyHoursResolver $companyHoursResolver
    ) {}

    public function __invoke(UpdateShiftCommand $command): UpdateShiftResponse
    {
        $shift = $this->shiftWriteRepository->findById($command->shiftId);

        if (null === $shift) {
            throw new TranslatableException('handler.shift.not_found', ['%id%' => $command->shiftId]);
        }

        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        // El coordinador necesita alcance tanto sobre el dueño actual del
        // turno como sobre el nuevo (si lo está reasignando a otro empleado).
        $this->scopeGuard->assertCanManageUser($command->actingUserId, $shift->getUserId());
        $this->scopeGuard->assertCanManageUser($command->actingUserId, $command->userId);

        $startAt = $this->parseDateTime($command->startAt);
        $endAt = $this->parseDateTime($command->endAt);

        $this->assertWithinBusinessHours($command->userId, $startAt, $endAt);

        $shift->reschedule($command->userId, $startAt, $endAt);

        $this->shiftWriteRepository->save($shift);

        return new UpdateShiftResponse(
            $shift->getId(),
            $shift->getUserId(),
            $shift->getStartAt()->format('Y-m-d\TH:i:s'),
            $shift->getEndAt()->format('Y-m-d\TH:i:s')
        );
    }

    private function parseDateTime(string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new TranslatableException('domain.shift.invalid_date');
        }
    }

    private function assertWithinBusinessHours(string $userId, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): void
    {
        $company = $this->companyHoursResolver->resolveForUser($userId);

        if (null === $company) {
            return;
        }

        if ($startAt->format('H:i:s') < $company->getOpeningTime()->format('H:i:s')
            || $endAt->format('H:i:s') > $company->getClosingTime()->format('H:i:s')) {
            throw new TranslatableException('domain.shift.outside_business_hours', [
                '%opening%' => $company->getOpeningTime()->format('H:i'),
                '%closing%' => $company->getClosingTime()->format('H:i'),
            ]);
        }
    }
}
