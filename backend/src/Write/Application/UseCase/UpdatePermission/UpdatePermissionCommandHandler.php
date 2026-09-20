<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdatePermission;

use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\PermissionWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePermissionCommandHandler
{
    public function __construct(
        private PermissionWriteRepositoryInterface $permissionWriteRepository
    ) {}

    public function __invoke(UpdatePermissionCommand $command): UpdatePermissionResponse
    {
        $permission = $this->permissionWriteRepository->findById($command->id);

        if (null === $permission) {
            throw new TranslatableException('handler.permission.not_found', ['%id%' => $command->id]);
        }

        $permission->setDescription($command->description);
        $this->permissionWriteRepository->save($permission);

        return new UpdatePermissionResponse($permission->getId(), $permission->getName(), $permission->getDescription());
    }
}
