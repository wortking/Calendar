<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ForgotPassword;

use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Event\PasswordResetRequestedEvent;
use App\Write\Domain\Model\PasswordResetToken;
use App\Write\Domain\Repository\PasswordResetTokenWriteRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class ForgotPasswordCommandHandler
{
    private const CODE_TTL_MINUTES = 15;

    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private PasswordResetTokenWriteRepositoryInterface $tokenRepository,
        private MessageBusInterface $eventBus,
        private RequestStack $requestStack
    ) {}

    public function __invoke(ForgotPasswordCommand $command): ForgotPasswordResponse
    {
        $user = $this->userRepository->findByEmail($command->email);

        // Respuesta siempre igual exista o no el email, para no filtrar qué cuentas existen.
        if (null === $user) {
            return new ForgotPasswordResponse(true);
        }

        $code = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $codeHash = hash('sha256', $code);

        $token = new PasswordResetToken(
            Uuid::v7()->toRfc4122(),
            $user->getId(),
            $codeHash,
            new \DateTimeImmutable(sprintf('+%d minutes', self::CODE_TTL_MINUTES))
        );

        $this->tokenRepository->save($token);

        $this->eventBus->dispatch(new PasswordResetRequestedEvent(
            $user->getId(),
            $user->getEmail(),
            $code,
            $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es'
        ));

        return new ForgotPasswordResponse(true);
    }
}
