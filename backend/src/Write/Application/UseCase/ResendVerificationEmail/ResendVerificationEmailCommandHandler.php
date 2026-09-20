<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ResendVerificationEmail;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Application\Service\EmailVerificationTokenIssuer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ResendVerificationEmailCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private EmailVerificationTokenIssuer $tokenIssuer,
        private RequestStack $requestStack
    ) {}

    public function __invoke(ResendVerificationEmailCommand $command): ResendVerificationEmailResponse
    {
        $user = $this->userReadRepository->findById($command->userId);

        if (null === $user) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        if (null !== $user->getEmailVerifiedAt()) {
            return new ResendVerificationEmailResponse(alreadyVerified: true);
        }

        $this->tokenIssuer->issue(
            $user->getId(),
            $user->getEmail(),
            $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es'
        );

        return new ResendVerificationEmailResponse(alreadyVerified: false);
    }
}
