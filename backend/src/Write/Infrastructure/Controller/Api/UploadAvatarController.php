<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Domain\Model\User;
use App\Write\Application\UseCase\ActivateUserImage\ActivateUserImageCommand;
use App\Write\Application\UseCase\AddUserImage\AddUserImageCommand;
use App\Write\Application\UseCase\AddUserImage\AddUserImageResponse;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Usuarios')]
class UploadAvatarController extends AbstractController
{
    private const MAX_SIZE = '5M';
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse,
        private RateLimiterFactory $uploadAvatarLimiter,
        #[Autowire('%kernel.project_dir%/public/uploads/avatars')]
        private string $avatarUploadDir
    ) {}

    #[Route('/api/me/avatar', name: 'api_me_upload_avatar', methods: ['POST'])]
    #[OA\Post(
        path: '/api/me/avatar',
        summary: 'Subir/reemplazar el avatar del usuario autenticado',
        description: 'multipart/form-data con un campo "image" (jpeg/png/webp, máx. 5MB). Queda activo de inmediato.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Avatar actualizado'),
            new OA\Response(response: 400, description: 'Archivo faltante o inválido'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 429, description: 'Demasiadas solicitudes'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $limit = $this->uploadAvatarLimiter->create($currentUser->getId())->consume();
        if (!$limit->isAccepted()) {
            $response = $this->apiResponse->error('security.rate_limit.exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) max(0, $limit->getRetryAfter()->getTimestamp() - time()));

            return $response;
        }

        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->apiResponse->error('validation.avatar.not_blank', Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($file, [
            new Assert\Image(
                maxSize: self::MAX_SIZE,
                mimeTypes: self::ALLOWED_MIME_TYPES,
                mimeTypesMessage: 'validation.avatar.invalid_type',
                maxSizeMessage: 'validation.avatar.too_large'
            ),
        ]);

        if (count($violations) > 0) {
            $errorMessages = [];
            foreach ($violations as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        $filename = Uuid::v7()->toRfc4122() . '.' . ($file->guessExtension() ?? 'bin');

        try {
            $file->move($this->avatarUploadDir, $filename);
        } catch (\Throwable) {
            return $this->apiResponse->error('validation.avatar.upload_failed', Response::HTTP_BAD_REQUEST);
        }

        $url = $request->getSchemeAndHttpHost() . '/uploads/avatars/' . $filename;

        try {
            $addEnvelope = $this->messageBus->dispatch(new AddUserImageCommand($currentUser->getId(), $url));

            /** @var AddUserImageResponse|null $addResult */
            $addResult = $addEnvelope->last(HandledStamp::class)?->getResult();

            if (null !== $addResult) {
                $this->messageBus->dispatch(new ActivateUserImageCommand($currentUser->getId(), $addResult->id));
            }
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \InvalidArgumentException) {
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        return $this->apiResponse->success(['avatarUrl' => $url], 'controller.avatar.updated');
    }
}
