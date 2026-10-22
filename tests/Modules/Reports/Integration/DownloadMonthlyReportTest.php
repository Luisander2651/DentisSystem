<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Service\MonthlyReportService;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use App\Modules\Reports\Infrastructure\Export\OpenSpoutMonthlyReportExporter;
use Carbon\CarbonImmutable;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA8 and CA9: the report of a month as an Excel file with four sheets, the figures of
 * the screen, percentages as numbers with one decimal and signed differences as numbers.
 */

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

/**
 * Every row of every sheet of the file, by sheet name, keeping empty rows and without the empty
 * cells the reader adds at the end of a short row.
 *
 * @return array<string, list<list<mixed>>>
 */
function downloadedWorkbook(string $path): array
{
    $reader = new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));
    $reader->open($path);
    $sheets = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->toArray();

            // The reader pads a short row with empty cells up to the widest row of the sheet.
            while ($cells !== [] && end($cells) === '') {
                array_pop($cells);
            }

            $sheets[$sheet->getName()][] = $cells;
        }
    }

    $reader->close();

    return $sheets;
}

/** The number format of a cell (such as "B11") of a sheet (1 = the first), read from styles.xml. */
function downloadedCellFormat(string $path, int $sheet, string $cell): ?string
{
    $zip = new ZipArchive;
    $zip->open($path);
    $styles = simplexml_load_string((string) $zip->getFromName('xl/styles.xml'));
    $worksheet = simplexml_load_string((string) $zip->getFromName("xl/worksheets/sheet{$sheet}.xml"));
    $zip->close();

    $worksheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $node = $worksheet->xpath("//x:c[@r='{$cell}']")[0] ?? null;

    if ($node === null) {
        return null;
    }

    $numFmtId = (int) ($styles->cellXfs->xf[(int) ($node['s'] ?? 0)]['numFmtId'] ?? 0);

    foreach ($styles->numFmts->numFmt ?? [] as $format) {
        if ((int) $format['numFmtId'] === $numFmtId) {
            return (string) $format['formatCode'];
        }
    }

    return null;
}

/** Downloads the file of a month and keeps it where the test can read it. */
function downloadReport(object $test, string $month): string
{
    $response = (fn () => $this->get($this->monthlyReportExcelUrl($month)))->call($test);
    $response->assertOk();

    $copy = tempnam(sys_get_temp_dir(), 'reporte-test-');
    copy($response->baseResponse->getFile()->getPathname(), $copy);

    return $copy;
}

it('sends an Excel file named after the month', function () {
    $this->seedJuly2026();

    $response = $this->get($this->monthlyReportExcelUrl('2026-07'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('reporte-dentissa-2026-07.xlsx');
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment;')->toContain('reporte-dentissa-2026-07.xlsx');
});

it('has the four sheets in order, and the summary of July 2026 as the design shows it', function () {
    $this->seedJuly2026();
    $workbook = downloadedWorkbook(downloadReport($this, '2026-07'));

    expect(array_keys($workbook))->toBe(['Resumen', 'Tratamientos', 'Staff', 'Demanda'])
        ->and(array_slice($workbook['Resumen'], 0, 14))->toEqual([
            ['Dentissa · Reporte de julio de 2026'],
            ['Generado el 19/10/2026 10:00 · Comparado con junio de 2026'],
            [],
            ['Indicador', 'Julio de 2026', 'Junio de 2026', 'Diferencia'],
            ['Total de citas', 8, 0, 8],
            ['Completadas', 2, 0, 2],
            ['Canceladas', 1, 0, 1],
            ['Sin cerrar', 5, 0, 5],
            ['Reprogramadas pendientes', 0, 0, 0],
            ['Asignadas pendientes', 0, 0, 0],
            ['Tasa de asistencia', 0.286, 'Sin datos', '—'],
            ['Tasa de cancelación', 0.125, 'Sin datos', '—'],
            ['Pacientes nuevos', 0, 0, 0],
            ['Pacientes activos (al 19/10/2026)', 2, '—', '—'],
        ])
        ->and($workbook['Tratamientos'])->toEqual([
            ['N.º', 'Tratamiento', 'Citas'],
            [1, 'Limpieza dental de prueba', 3],
            [2, 'Ortodoncia de prueba', 2],
            [3, 'Resina de prueba', 2],
        ])
        ->and($workbook['Staff'])->toEqual([
            ['N.º', 'Nombre', 'Citas', 'Completadas'],
            [1, 'Daniela Prueba', 5, 2],
            [2, 'Diego Prueba', 2, 0],
        ])
        ->and($workbook['Demanda'])->toEqual([
            ['Hora', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom', 'Total'],
            ['9:00', 1, 0, 1, 0, 0, 0, 0, 2],
            ['10:00', 0, 1, 0, 0, 0, 0, 0, 1],
            ['11:00', 0, 0, 1, 0, 0, 0, 0, 1],
            ['12:00', 1, 0, 1, 0, 0, 0, 0, 2],
            ['13:00', 0, 0, 0, 0, 0, 0, 0, 0],
            ['14:00', 0, 0, 0, 0, 0, 0, 0, 0],
            ['15:00', 0, 0, 0, 0, 0, 0, 0, 0],
            ['16:00', 0, 0, 1, 0, 0, 0, 0, 1],
            ['Total', 2, 1, 4, 0, 0, 0, 0, 7],
        ]);
});

it('writes rates as numbers in percent with one decimal and differences as signed numbers', function () {
    $this->seedJuly2026();
    $path = downloadReport($this, '2026-07');

    expect(downloadedCellFormat($path, 1, 'B11'))->toBe('0.0%')
        ->and(downloadedCellFormat($path, 1, 'B12'))->toBe('0.0%')
        ->and(downloadedCellFormat($path, 1, 'D5'))->toBe('+0;"−"0;0');
});

it('writes a fall as a negative number and a rate difference in points', function () {
    $this->seedJuly2026();
    $this->createAppointment(['date' => '2026-08-03', 'status' => 'completada']);
    $this->createAppointment(['date' => '2026-08-04', 'status' => 'asignada']);
    $path = downloadReport($this, '2026-08');
    $summary = downloadedWorkbook($path)['Resumen'];

    expect($summary[4])->toEqual(['Total de citas', 2, 8, -6])
        ->and($summary[10])->toEqual(['Tasa de asistencia', 0.5, 0.286, 21.4])
        ->and(downloadedCellFormat($path, 1, 'D5'))->toBe('+0;"−"0;0')
        ->and(downloadedCellFormat($path, 1, 'D11'))->toBe('+0.0;"−"0.0;0.0');
});

it('writes no data as text in a month without appointments', function () {
    $workbook = downloadedWorkbook(downloadReport($this, '2026-08'));

    expect($workbook['Resumen'][10])->toBe(['Tasa de asistencia', 'Sin datos', 'Sin datos', '—'])
        ->and($workbook['Tratamientos'])->toBe([['N.º', 'Tratamiento', 'Citas']])
        ->and($workbook['Staff'])->toBe([['N.º', 'Nombre', 'Citas', 'Completadas']])
        ->and($workbook['Demanda'])->toBe([['Hora', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom', 'Total']]);
});

it('warns in the summary that the month in course only has data until today', function () {
    $current = downloadedWorkbook(downloadReport($this, '2026-10'));
    $past = downloadedWorkbook(downloadReport($this, '2026-09'));

    expect($current['Resumen'][2])->toBe(['Mes en curso: datos hasta hoy, 19/10/2026.'])
        ->and($past['Resumen'][2])->toBe([]);
});

it('deletes the temporary file once it has been sent (D10)', function () {
    $response = $this->get($this->monthlyReportExcelUrl('2026-07'));
    $response->assertOk();

    expect($response->baseResponse)->toBeInstanceOf(BinaryFileResponse::class);
    $path = $response->baseResponse->getFile()->getPathname();
    expect(file_exists($path))->toBeTrue();

    ob_start();
    $response->baseResponse->sendContent();
    ob_end_clean();

    expect(file_exists($path))->toBeFalse();
});

it('writes the file in its own folder and leaves nothing there when the file cannot be written (R4, R37)', function () {
    $this->seedJuly2026();
    $report = app(MonthlyReportService::class)->build(ReportMonth::create('2026-07', now()), now());
    $brokenWeek = MonthlyReport::create(
        month: $report->month,
        dataUntil: $report->dataUntil,
        appointments: $report->appointments,
        rates: $report->rates,
        newPatients: $report->newPatients,
        activePatients: $report->activePatients,
        activeAsOf: $report->activeAsOf,
        treatments: $report->treatments,
        staff: $report->staff,
        demand: ['hours' => [9], 'matrix' => [['not a count']], 'by_weekday' => array_fill(0, 7, 0), 'by_hour' => [0], 'top_weekdays' => [], 'top_hours' => [], 'total' => 0],
    );
    $folder = sys_get_temp_dir().'/reporte-dentissa-test-'.bin2hex(random_bytes(8));
    mkdir($folder);
    $exporter = new OpenSpoutMonthlyReportExporter($folder);

    try {
        $path = $exporter->export($report);

        expect(dirname($path))->toBe($folder)
            ->and(downloadedWorkbook($path))->toHaveKey('Resumen');

        unlink($path);

        expect(fn () => $exporter->export($brokenWeek))->toThrow(TypeError::class)
            ->and(array_values(array_diff(scandir($folder) ?: [], ['.', '..'])))->toBe([]);
    } finally {
        array_map('unlink', glob($folder.'/*') ?: []);
        rmdir($folder);
    }
});
