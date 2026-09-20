<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\UpdateRole;

use App\Shared\Domain\Exception\TranslatableException;
use App\Write\Domain\Repository\RoleWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateRoleCommandHandler
{
    public function __construct(
        private RoleWriteRepositoryInterface $roleWriteRepository
    ) {}

    public function __invoke(UpdateRoleCommand $command): UpdateRoleResponse
    {
        $role = $this->roleWriteRepository->findById($command->id);

        if (null === $role) {
            throw new TranslatableException('handler.role.not_found_by_id', ['%id%' => $command->id]);
        }

        $role->setDescription($command->description);
        $this->roleWriteRepository->save($role);

        return new UpdateRoleResponse($role->getId(), $role->getName(), $role->getDescription());
    }
}
