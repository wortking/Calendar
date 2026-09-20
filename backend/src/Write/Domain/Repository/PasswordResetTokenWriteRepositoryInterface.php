<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\PasswordResetToken;

interface PasswordResetTokenWriteRepositoryInterface
{
    public function save(PasswordResetToken $token): void;

    /**
     * Busca un código válido (no usado, no caducado) para ese usuario y lo marca como usado.
     *
     * @return bool true si el código era válido y se consumió, false si no existía/ya expiró/ya se usó
     */
    public function consume(string $userId, string $codeHash): bool;
}
