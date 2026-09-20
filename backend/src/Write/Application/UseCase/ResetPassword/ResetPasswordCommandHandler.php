<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResetPassword;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Event\PasswordChangedEvent;
use App\Write\Domain\Repository\PasswordResetTokenWriteRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class ResetPasswordCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private PasswordResetTokenWriteRepositoryInterface $tokenRepository,
        private UserWriteRepositoryInterface $userWriteRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $eventBus,
        private RequestStack $requestStack
    ) {}

    public function __invoke(ResetPasswordCommand $command): ResetPasswordResponse
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (null === $user) {
            throw new TranslatableException('handler.reset_code.invalid');
        }

        $codeHash = hash('sha256', $command->code);

        if (!$this->tokenRepository->consume($user->getId(), $codeHash)) {
            throw new TranslatableException('handler.reset_code.invalid');
        }

        $newHashedPassword = $this->passwordHasher->hashPassword($user, $command->newPassword);
        $this->userWriteRepository->changePassword($user->getId(), $newHashedPassword);

        $this->eventBus->dispatch(new PasswordChangedEvent(
            $user->getId(),
            $user->getEmail(),
            $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es'
        ));

        return new ResetPasswordResponse(true);
    }
}
