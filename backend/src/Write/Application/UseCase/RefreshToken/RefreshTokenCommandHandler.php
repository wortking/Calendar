<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\RefreshToken;

use App\Shared\Domain\Exception\TranslatableException;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\RefreshToken;
use App\Write\Domain\Repository\RefreshTokenWriteRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RefreshTokenCommandHandler
{
    public function __construct(
        private RefreshTokenWriteRepositoryInterface $refreshTokenRepository,
        private UserReadRepositoryInterface $userRepository,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function __invoke(RefreshTokenCommand $command): RefreshTokenResponse
    {
        $providedHash = hash('sha256', $command->refreshToken);

        // consume() revoca el token existente al validarlo (rotación: un
        // refresh token es de un solo uso, así se detecta su robo/reuso).
        $userId = $this->refreshTokenRepository->consume($providedHash);

        if (null === $userId) {
            throw new TranslatableException('handler.refresh_token.invalid');
        }

        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $userId]);
        }

        $newJwt = $this->jwtManager->create($user);

        $rawRefreshToken = bin2hex(random_bytes(32));
        $newToken = new RefreshToken(
            Uuid::v7()->toRfc4122(),
            $userId,
            hash('sha256', $rawRefreshToken),
            new \DateTimeImmutable(sprintf('+%d days', RefreshToken::TTL_DAYS))
        );
        $this->refreshTokenRepository->save($newToken);

        return new RefreshTokenResponse($newJwt, $rawRefreshToken);
    }
}
