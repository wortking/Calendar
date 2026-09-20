<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\ActivateUserImage;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Cache\CacheKeys;
use App\Read\Domain\Repository\UserImageReadRepositoryInterface;
use App\Write\Domain\Repository\UserImageWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
class ActivateUserImageCommandHandler
{
    public function __construct(
        private UserImageReadRepositoryInterface $imageReadRepository,
        private UserImageWriteRepositoryInterface $imageWriteRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(ActivateUserImageCommand $command): ActivateUserImageResponse
    {
        $image = $this->imageReadRepository->findById($command->imageId);

        if (null === $image || $image->getUserId() !== $command->userId) {
            throw new TranslatableException('handler.user_image.not_found', ['%id%' => $command->imageId]);
        }

        $this->imageWriteRepository->deactivateAllForUser($command->userId);
        $this->imageWriteRepository->activate($command->imageId);

        $this->cache->delete(CacheKeys::userImages($command->userId));

        return new ActivateUserImageResponse($command->imageId);
    }
}
