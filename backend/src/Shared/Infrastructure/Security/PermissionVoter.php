<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Read\Domain\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autoriza atributos de permiso granular (p.ej. "users.edit"), a diferencia de
 * los roles (ROLE_*) que ya gestiona el RoleVoter integrado de Symfony.
 */
class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_contains($attribute, '.');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->hasPermission($attribute);
    }
}
