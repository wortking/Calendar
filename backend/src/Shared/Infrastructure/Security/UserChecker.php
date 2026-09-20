<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Read\Domain\Model\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Bloquea a un usuario dado de baja: se aplica tanto al iniciar sesión
 * (firewall "login") como en cada request autenticado con un JWT ya
 * emitido (firewall "api", que recarga el usuario en cada request vía el
 * mismo provider), así una baja corta el acceso de inmediato aunque el
 * token todavía no haya expirado.
 *
 * Se usa CustomUserMessageAccountStatusException (no DisabledException a
 * secas) porque Symfony enmascara cualquier AccountStatusException "plana"
 * como BadCredentialsException por defecto (para no filtrar por qué falló
 * el login); esta subclase está pensada justo para poder mostrar un
 * mensaje propio sin que se enmascare.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('security.login.account_disabled');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
