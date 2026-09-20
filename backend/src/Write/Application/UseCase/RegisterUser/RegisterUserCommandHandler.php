<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RegisterUser;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Application\Service\EmailVerificationTokenIssuer;
use App\Write\Application\Service\RandomPasswordGenerator;
use App\Write\Domain\Model\Role;
use App\Write\Domain\Model\User;
use App\Write\Domain\Repository\RoleAssignmentWriteRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RegisterUserCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private UserWriteRepositoryInterface $userWriteRepository,
        private RoleReadRepositoryInterface $roleReadRepository,
        private RoleAssignmentWriteRepositoryInterface $roleAssignmentRepository,
        private EmailVerificationTokenIssuer $emailVerificationTokenIssuer,
        private RandomPasswordGenerator $randomPasswordGenerator,
        private UserPasswordHasherInterface $passwordHasher,
        private RequestStack $requestStack
    ) {}

    public function __invoke(RegisterUserCommand $command): RegisterUserResponse
    {
        if (null !== $this->userReadRepository->findByEmail($command->email)) {
            throw new TranslatableException('handler.register_user.email_taken', ['%email%' => $command->email]);
        }

        $id = Uuid::v7()->toRfc4122();
        $user = new User($id, $command->email, 'temporary');

        // La contraseña la genera el sistema, nunca el admin que crea la
        // cuenta: se le manda al usuario por email (junto con el link de
        // verificación) y queda obligado a cambiarla en su primer login.
        $temporaryPassword = $this->randomPasswordGenerator->generate();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $temporaryPassword);
        $user->changePassword($hashedPassword);
        $user->requirePasswordChange();

        $this->userWriteRepository->save($user);

        $role = $this->roleReadRepository->findByName(Role::ROLE_USER);
        if (null !== $role) {
            $this->roleAssignmentRepository->assignRole($id, $role->getId());
        }

        $this->emailVerificationTokenIssuer->issue(
            $id,
            $command->email,
            $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es',
            $temporaryPassword
        );

        return new RegisterUserResponse($id, $command->email);
    }
}
