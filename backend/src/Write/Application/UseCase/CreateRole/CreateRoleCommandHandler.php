<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreateRole;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\RoleReadRepositoryInterface;
use App\Write\Domain\Model\Role;
use App\Write\Domain\Repository\RoleWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateRoleCommandHandler
{
    public function __construct(
        private RoleReadRepositoryInterface $roleReadRepository,
        private RoleWriteRepositoryInterface $roleWriteRepository
    ) {}

    public function __invoke(CreateRoleCommand $command): CreateRoleResponse
    {
        if (null !== $this->roleReadRepository->findByName($command->name)) {
            throw new TranslatableException('handler.role.name_taken', ['%name%' => $command->name]);
        }

        $id = Uuid::v7()->toRfc4122();
        $role = new Role($id, $command->name, $command->description);

        $this->roleWriteRepository->save($role);

        return new CreateRoleResponse($role->getId(), $role->getName(), $role->getDescription());
    }
}
