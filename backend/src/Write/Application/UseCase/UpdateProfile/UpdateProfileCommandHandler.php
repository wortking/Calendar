<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateProfile;

use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateProfileCommandHandler
{
    public function __construct(
        private UserWriteRepositoryInterface $userWriteRepository
    ) {}

    public function __invoke(UpdateProfileCommand $command): UpdateProfileResponse
    {
        $this->userWriteRepository->updateProfile(
            $command->userId,
            $command->firstName,
            $command->lastName,
            $command->dni,
            $command->sex
        );

        return new UpdateProfileResponse(true);
    }
}
