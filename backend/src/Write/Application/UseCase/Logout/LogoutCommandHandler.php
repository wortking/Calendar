<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\Logout;

use App\Shared\Infrastructure\Security\JwtBlocklist;
use App\Write\Domain\Repository\RefreshTokenWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LogoutCommandHandler
{
    public function __construct(
        private JwtBlocklist $blocklist,
        private RefreshTokenWriteRepositoryInterface $refreshTokenRepository
    ) {}

    public function __invoke(LogoutCommand $command): LogoutResponse
    {
        $this->blocklist->block($command->jti, $command->accessTokenTtlSeconds);

        if (null !== $command->refreshToken) {
            $this->refreshTokenRepository->revoke(hash('sha256', $command->refreshToken));
        }

        return new LogoutResponse(true);
    }
}
