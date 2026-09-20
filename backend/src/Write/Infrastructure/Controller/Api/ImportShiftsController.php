<?php

declare(strict_types=1);

namespace App\Write\Infrastructure\Controller\Api;

use App\Shared\Domain\Exception\TranslatableException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Read\Domain\Model\User;
use App\Read\Domain\Repository\UserReadRepositoryInterface;
use App\Write\Application\UseCase\CreateShift\CreateShiftCommand;
use OpenApi\Attributes as OA;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Carga masiva de turnos desde un Excel/CSV con columnas fijas: Email,
 * Fecha, Hora inicio, Hora fin. Cada fila se procesa como un alta de turno
 * independiente (vía CreateShiftCommand, para reutilizar toda su
 * validación: existencia del usuario, alcance de departamento, horario de
 * la empresa); una fila con error no aborta el resto, se reporta en
 * "errors" con su número de fila.
 */
#[OA\Tag(name: 'Turnos')]
class ImportShiftsController extends AbstractController
{
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function __construct(
        private MessageBusInterface $messageBus,
        private ApiResponse $apiResponse,
        private TranslatorInterface $translator,
        private UserReadRepositoryInterface $userRepository
    ) {}

    #[Route('/api/shifts/import', name: 'api_shifts_import', methods: ['POST'])]
    #[IsGranted('shifts.manage')]
    #[OA\Post(
        path: '/api/shifts/import',
        summary: 'Importar turnos desde un Excel/CSV',
        description: 'Requiere el permiso "shifts.manage". multipart/form-data con un campo "file" (columnas: Email, Fecha, Hora inicio, Hora fin). Cada fila se valida por separado; las que fallan se listan en "errors" sin abortar el resto.',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Importación procesada (ver "created"/"errors" en la respuesta)'),
            new OA\Response(response: 400, description: 'Archivo faltante o inválido'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->apiResponse->error('validation.import_file.not_blank', Response::HTTP_BAD_REQUEST);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $this->apiResponse->error('validation.import_file.invalid_type', Response::HTTP_BAD_REQUEST);
        }

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (\Throwable) {
            return $this->apiResponse->error('validation.import_file.invalid', Response::HTTP_BAD_REQUEST);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $created = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; ++$row) {
            $email = trim((string) $sheet->getCell([1, $row])->getCalculatedValue());

            if ('' === $email) {
                continue;
            }

            $user = $this->userRepository->findByEmail($email);
            if (null === $user) {
                $errors[] = ['row' => $row, 'message' => $this->translator->trans('handler.import.user_not_found', ['%email%' => $email])];
                continue;
            }

            $date = $this->cellToDate($sheet->getCell([2, $row]));
            $startTime = $this->cellToTime($sheet->getCell([3, $row]));
            $endTime = $this->cellToTime($sheet->getCell([4, $row]));

            if (null === $date || null === $startTime || null === $endTime) {
                $errors[] = ['row' => $row, 'message' => $this->translator->trans('handler.import.invalid_row')];
                continue;
            }

            $command = new CreateShiftCommand(
                $user->getId(),
                "{$date} {$startTime}:00",
                "{$date} {$endTime}:00",
                $currentUser->getId()
            );

            try {
                $this->messageBus->dispatch($command);
                ++$created;
            } catch (HandlerFailedException $e) {
                $previous = $e->getPrevious();
                $message = $previous instanceof TranslatableException
                    ? $this->translator->trans($previous->getTranslationKey(), $previous->getTranslationParams())
                    : $this->translator->trans('handler.import.invalid_row');

                $errors[] = ['row' => $row, 'message' => $message];
            }
        }

        return $this->apiResponse->success(['created' => $created, 'errors' => $errors], 'controller.shift.imported');
    }

    private function cellToDate(Cell $cell): ?string
    {
        $value = $cell->getCalculatedValue();

        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        $text = trim((string) $value);
        if ('' === $text) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($text))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function cellToTime(Cell $cell): ?string
    {
        $value = $cell->getCalculatedValue();

        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject($value)->format('H:i');
        }

        $text = trim((string) $value);
        if (1 === preg_match('/^(\d{1,2}):(\d{2})/', $text, $matches)) {
            return sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        }

        return null;
    }
}
