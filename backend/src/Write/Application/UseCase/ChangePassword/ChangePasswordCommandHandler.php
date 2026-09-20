<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ChangePassword;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Event\PasswordChangedEvent;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class ChangePasswordCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private UserWriteRepositoryInterface $userWriteRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $eventBus,
        private RequestStack $requestStack
    ) {}

    public function __invoke(ChangePasswordCommand $command): ChangePasswordResponse
    {
        $user = $this->userReadRepository->findById($command->userId);

        if (null === $user) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $command->currentPassword)) {
            throw new TranslatableException('handler.change_password.invalid_current');
        }

        $newHashedPassword = $this->passwordHasher->hashPassword($user, $command->newPassword);
        $this->userWriteRepository->changePassword($command->userId, $newHashedPassword);

        $this->eventBus->dispatch(new PasswordChangedEvent(
            $command->userId,
            $user->getEmail(),
            $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es'
        ));

        return new ChangePasswordResponse(true);
    }
}
