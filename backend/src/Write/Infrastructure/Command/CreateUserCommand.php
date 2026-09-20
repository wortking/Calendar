<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Command;

use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\Role;
use App\Write\Domain\Model\User;
use App\Write\Domain\Repository\RoleAssignmentWriteRepositoryInterface;
use App\Write\Domain\Repository\UserWriteRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crea un nuevo usuario con email, contraseña y rol.'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private UserWriteRepositoryInterface $userWriteRepository,
        private RoleReadRepositoryInterface $roleReadRepository,
        private RoleAssignmentWriteRepositoryInterface $roleAssignmentRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email del usuario')
            ->addArgument('password', InputArgument::REQUIRED, 'Contraseña en texto plano')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Otorga el rol ROLE_ADMIN además de ROLE_USER');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');
        $roleNames = $input->getOption('admin') ? [Role::ROLE_USER, Role::ROLE_ADMIN] : [Role::ROLE_USER];

        if (null !== $this->userReadRepository->findByEmail($email)) {
            $io->error(sprintf('Ya existe un usuario con el email "%s".', $email));

            return Command::FAILURE;
        }

        $id = Uuid::v7()->toRfc4122();
        $user = new User($id, $email, 'temporary');
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->changePassword($hashedPassword);

        $this->userWriteRepository->save($user);

        foreach ($roleNames as $roleName) {
            $role = $this->roleReadRepository->findByName($roleName);

            if (null === $role) {
                $io->warning(sprintf('El rol "%s" no existe, se omite la asignación.', $roleName));
                continue;
            }

            $this->roleAssignmentRepository->assignRole($id, $role->getId());
        }

        $io->success(sprintf('Usuario "%s" creado correctamente con id "%s" y roles [%s].', $email, $id, implode(', ', $roleNames)));

        return Command::SUCCESS;
    }
}
