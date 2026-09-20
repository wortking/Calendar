<?php

declare(strict_types=1);

namespace App\Write\Application\UseCase\AddUserImage;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Cache\CacheKeys;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Domain\Model\UserImage;
use App\Write\Domain\Repository\UserImageWriteRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
class AddUserImageCommandHandler
{
    public function __construct(
        private UserReadRepositoryInterface $userRepository,
        private UserImageWriteRepositoryInterface $imageRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(AddUserImageCommand $command): AddUserImageResponse
    {
        if (null === $this->userRepository->findById($command->userId)) {
            throw new TranslatableException('handler.user.not_found', ['%id%' => $command->userId]);
        }

        $id = Uuid::v7()->toRfc4122();

        $image = new UserImage($id, $command->userId, $command->url, new \DateTimeImmutable());

        $this->imageRepository->save($image);

        $this->cache->delete(CacheKeys::userImages($command->userId));

        return new AddUserImageResponse($image->getId());
    }
}
