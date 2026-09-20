<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Write\Application\UseCase\CopyWeekShifts\CopyWeekShiftsCommand;
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
class CopyWeekShiftsController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ValidatorInterface $validator,
        private ApiResponse $apiResponse
    ) {}

    #[Route('/api/shifts/copy-previous-week', name: 'api_shifts_copy_previous_week', methods: ['POST'])]
    #[IsGranted('shifts.manage')]
    #[OA\Post(
        path: '/api/shifts/copy-previous-week',
        summary: 'Copiar los turnos de la semana anterior a la semana indicada',
        description: 'Requiere el permiso "shifts.manage". Un coordinador solo copia turnos de su departamento. Los turnos que ya existan en el destino (mismo usuario y horario) se omiten, no se duplican. Si "includeRoomActivities" es true, también se copian las actividades de sala de esos días (se omiten si la sala o el empleado ya están ocupados en el destino, o si el turno correspondiente no se pudo copiar).',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['weekStart'],
                properties: [
                    new OA\Property(property: 'weekStart', type: 'string', format: 'date', example: '2026-09-21', description: 'Primer día de la semana destino; se copia la semana de los 7 días previos.'),
                    new OA\Property(property: 'includeRoomActivities', type: 'boolean', example: false, description: 'Copiar también las actividades de sala de esos días.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Turnos copiados (ver "copied"/"skipped"/"activitiesCopied"/"activitiesSkipped" en la respuesta)'),
            new OA\Response(response: 400, description: 'Error de validación'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $command = new CopyWeekShiftsCommand(
            (string) ($data['weekStart'] ?? ''),
            $currentUser->getId(),
            (bool) ($data['includeRoomActivities'] ?? false)
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
                return $this->apiResponse->error($previous, Response::HTTP_BAD_REQUEST);
            }

            throw $e;
        }

        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->apiResponse->success($response->serialize(), 'controller.shift.week_copied');
    }
}
