<?php

declare(strict_types=1);

namespace App\Read\Infrastructure\Controller\Api;

use OpenApi\Attributes as OA;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Turnos')]
class DownloadShiftsImportTemplateController extends AbstractController
{
    #[Route('/api/shifts/import-template', name: 'api_shifts_import_template', methods: ['GET'])]
    #[IsGranted('shifts.manage')]
    #[OA\Get(
        path: '/api/shifts/import-template',
        summary: 'Descargar la plantilla Excel para importar turnos',
        description: 'Requiere el permiso "shifts.manage".',
        tags: ['Turnos'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Archivo .xlsx de plantilla'),
        ]
    )]
    public function __invoke(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            [
                ['Email', 'Fecha', 'Hora inicio', 'Hora fin'],
                ['empleado@example.com', '2026-09-21', '09:00', '17:00'],
            ],
            null,
            'A1'
        );

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(20);
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="plantilla_turnos.xlsx"');

        return $response;
    }
}
