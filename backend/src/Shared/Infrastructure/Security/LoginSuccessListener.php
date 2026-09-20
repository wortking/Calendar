<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Read\Domain\Model\User;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
class LoginSuccessListener
{
    public function __construct(
        private UserWriteRepositoryInterface $userWriteRepository
    ) {}

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->userWriteRepository->recordLogin($user->getId(), new \DateTimeImmutable());
    }
}
