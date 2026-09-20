<?php

declare(strict_types=1);

namespace App\Write\Domain\Repository;

use App\Write\Domain\Model\RefreshToken;

interface RefreshTokenWriteRepositoryInterface
{
    public function save(RefreshToken $token): void;

    /**
     * Busca un refresh token válido (no revocado, no caducado) por su hash
     * y lo revoca (uso único / rotación en cada refresh).
     *
     * @return string|null el userId asociado, o null si el token no era válido
     */
    public function consume(string $tokenHash): ?string;

    /**
     * Revoca un refresh token concreto (logout), sin efecto si ya no existe.
     */
    public function revoke(string $tokenHash): void;
}
