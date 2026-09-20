<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Read\Domain\Model\User;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private UserWriteRepositoryInterface $userWriteRepository
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userReadRepository->findByEmail($identifier);

        if (null === $user) {
            throw new UserNotFoundException('security.user_not_found_by_email');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException('security.unsupported_user_class');
        }

        $freshUser = $this->userReadRepository->findById($user->getId());

        if (null === $freshUser) {
            throw new UserNotFoundException('security.user_no_longer_exists');
        }

        return $freshUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->userWriteRepository->changePassword($user->getId(), $newHashedPassword);
    }
}
