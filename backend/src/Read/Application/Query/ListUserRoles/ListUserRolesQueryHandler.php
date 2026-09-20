<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUserRoles;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Cache\CacheKeys;
use App\Read\Domain\Repository\RoleAssignmentReadRepositoryInterface;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ListUserRolesQueryHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private RoleAssignmentReadRepositoryInterface $roleAssignmentRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(ListUserRolesQuery $query): ListUserRolesResponse
    {
        if (null === $this->userRepository->findById($query->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $query->userId]);
        }

        return $this->cache->get(
            CacheKeys::userRoles($query->userId),
            function (ItemInterface $item) use ($query): ListUserRolesResponse {
                $item->expiresAfter(CacheKeys::TTL_SECONDS);

                return new ListUserRolesResponse(
                    $this->roleAssignmentRepository->findRoleNamesByUserId($query->userId),
                    $this->roleAssignmentRepository->findPermissionNamesByUserId($query->userId)
                );
            }
        );
    }
}
