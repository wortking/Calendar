<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\CoverShift\CoverShiftCommand;
use App\Read\Domain\Model\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Turnos')]
class CoverShiftController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/shifts/{shiftId}/cover', name: 'api_shifts_cover', methods: ['POST'])]
    #[IsGranted('shifts.manage')]
    #[OA\Post(
        path: '/api/shifts/{shiftId}/cover',
        summary: 'Cubrir el resto de un turno con otro empleado',
        description: 'Requiere el permiso "shifts.manage". Acorta el turno a "cutoffAt", crea un turno nuevo para "replacementUserId" desde ahí hasta el fin original, y reasigna al reemplazante las actividades de sala que quedaron sin turno (se omiten sin fallar toda la operación las que el reemplazante ya no puede tomar por conflicto de horario).',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'shiftId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cutoffAt', 'replacementUserId'],
                properties: [
                    new OA\Property(property: 'cutoffAt', type: 'string', format: 'date-time', example: '2026-09-22T13:30:00', description: 'Hora en la que el empleado original deja el turno.'),
                    new OA\Property(property: 'replacementUserId', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Turno cubierto (ver detalle en la respuesta)'),
            new OA\Response(response: 400, description: 'Error de validación (horario de corte inválido, fuera del horario de la empresa, mismo usuario)'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'El turno o el reemplazante no existen'),
        ]
    )]
    public function __invoke(string $shiftId, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new CoverShiftCommand(
            $shiftId,
            (string) ($data['cutoffAt'] ?? ''),
            (string) ($data['replacementUserId'] ?? ''),
            $currentUser->getId()
        );

        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->apiResponse->error(implode('; ', $errorMessages), Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof TranslatableException) {
                $status = match (true) {
                    in_array($previous->getTranslationKey(), ['handler.shift.not_found', 'handler.user.not_found'], true) => Response::HTTP_NOT_FOUND,
                    str_starts_with($previous->getTranslationKey(), 'handler.access_denied.') => Response::HTTP_FORBIDDEN,
                    default => Response::HTTP_BAD_REQUEST,
                };

                return $this->apiResponse->error($previous, $status);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.shift.covered');
    }
}
