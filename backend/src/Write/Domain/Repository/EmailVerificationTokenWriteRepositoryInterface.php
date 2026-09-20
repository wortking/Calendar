<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\EmailVerificationToken;

interface EmailVerificationTokenWriteRepositoryInterface
{
    public function save(EmailVerificationToken $token): void;

    /**
     * Busca un token válido (no usado, no caducado) por su hash y lo marca como usado.
     *
     * @return string|null el id del usuario dueño del token si era válido, null si no existía/ya expiró/ya se usó
     */
    public function consume(string $tokenHash): ?string;
}
