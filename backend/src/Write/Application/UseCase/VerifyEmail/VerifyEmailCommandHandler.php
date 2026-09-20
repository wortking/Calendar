<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\VerifyEmail;

use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\EmailVerificationTokenWriteRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VerifyEmailCommandHandler
{
    public function __construct(
        private EmailVerificationTokenWriteRepositoryInterface $tokenRepository,
        private UserWriteRepositoryInterface $userWriteRepository
    ) {}

    public function __invoke(VerifyEmailCommand $command): VerifyEmailResponse
    {
        $tokenHash = hash('sha256', $command->token);

        $userId = $this->tokenRepository->consume($tokenHash);

        if (null === $userId) {
            throw new TranslatableException('handler.email_verification.invalid_token');
        }

        $this->userWriteRepository->markEmailVerified($userId, new \DateTimeImmutable());

        return new VerifyEmailResponse(true);
    }
}
