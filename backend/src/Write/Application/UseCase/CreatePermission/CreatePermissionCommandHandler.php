<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\CreatePermission;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\PermissionReadRepositoryInterface;
use App\Write\Domain\Model\Permission;
use App\Write\Domain\Repository\PermissionWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreatePermissionCommandHandler
{
    public function __construct(
        private PermissionReadRepositoryInterface $permissionReadRepository,
        private PermissionWriteRepositoryInterface $permissionWriteRepository
    ) {}

    public function __invoke(CreatePermissionCommand $command): CreatePermissionResponse
    {
        if (null !== $this->permissionReadRepository->findByName($command->name)) {
            throw new TranslatableException('handler.permission.name_taken', ['%name%' => $command->name]);
        }

        $id = Uuid::v7()->toRfc4122();
        $permission = new Permission($id, $command->name, $command->description);

        $this->permissionWriteRepository->save($permission);

        return new CreatePermissionResponse($permission->getId(), $permission->getName(), $permission->getDescription());
    }
}
