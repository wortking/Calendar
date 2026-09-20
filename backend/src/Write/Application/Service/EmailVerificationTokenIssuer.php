<?php

declare(strict_types=1);

namespace App\Write\Application\Service;

use App\Write\Domain\Event\EmailVerificationRequestedEvent;
use App\Write\Domain\Model\EmailVerificationToken;
use App\Write\Domain\Repository\EmailVerificationTokenWriteRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Genera y guarda un token de verificación de email nuevo y dispara el
 * evento que hace que se mande el mail. Usado tanto al registrarse como al
 * pedir un reenvío, para no duplicar la lógica (y la TTL) en dos handlers.
 */
class EmailVerificationTokenIssuer
{
    private const TOKEN_TTL_HOURS = 48;

    public function __construct(
        private EmailVerificationTokenWriteRepositoryInterface $tokenRepository,
        private MessageBusInterface $eventBus
    ) {}

    // $temporaryPassword: solo la pasa RegisterUserCommandHandler (para
    // incluirla en el mismo mail de verificación); el reenvío no la conoce
    // -la contraseña ya está hasheada en ese momento- y manda null.
    public function issue(string $userId, string $email, string $locale, ?string $temporaryPassword = null): void
    {
        $verificationToken = bin2hex(random_bytes(32));

        $this->tokenRepository->save(new EmailVerificationToken(
            Uuid::v7()->toRfc4122(),
            $userId,
            hash('sha256', $verificationToken),
            new \DateTimeImmutable(sprintf('+%d hours', self::TOKEN_TTL_HOURS))
        ));

        $this->eventBus->dispatch(new EmailVerificationRequestedEvent($userId, $email, $verificationToken, $locale, $temporaryPassword));
    }
}
