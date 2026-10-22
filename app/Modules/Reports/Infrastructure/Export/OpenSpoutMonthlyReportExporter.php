<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Export;

use App\Modules\Reports\Application\Contracts\MonthlyReportExporterInterface;
use App\Modules\Reports\Domain\ValueObjects\Comparison;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Spec 019 (CA8, CA9, CA12, CA13; ADR 0005): the report of a month as an .xlsx with the sheets
 * Resumen, Tratamientos, Staff and Demanda, from the same report the screen shows.
 *
 * Every text goes in a StringCell and every number in a NumericCell, written one by one. The
 * factories of Row and Cell that guess the type of a cell from its value are never used: they turn
 * a name that starts with "=" into a formula (TM4). A file that fails half written is deleted
 * before the error goes on, and that error is always the original one, even if closing fails too
 * (R4, R39).
 */
final class OpenSpoutMonthlyReportExporter implements MonthlyReportExporterInterface
{
    private const HEADER_BACKGROUND = 'FBE9EE';

    private const PERCENT = '0.0%';

    private const SIGNED_COUNT = '+0;"−"0;0';

    private const SIGNED_POINTS = '+0.0;"−"0.0;0.0';

    private const NO_DATA = 'Sin datos';

    private const NO_COMPARISON = '—';

    private const APPOINTMENT_ROWS = [
        'total' => 'Total de citas',
        'completed' => 'Completadas',
        'cancelled' => 'Canceladas',
        'unclosed' => 'Sin cerrar',
        'rescheduled_pending' => 'Reprogramadas pendientes',
        'assigned_pending' => 'Asignadas pendientes',
    ];

    private const RATE_ROWS = [
        'attendance' => 'Tasa de asistencia',
        'cancellation' => 'Tasa de cancelación',
    ];

    private const WEEKDAYS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    public function __construct(private ?string $temporaryDirectory = null) {}

    public function export(MonthlyReport $report): string
    {
        $path = (string) tempnam($this->temporaryDirectory ?? sys_get_temp_dir(), 'reporte-dentissa-');
        $writer = new Writer;

        try {
            $writer->openToFile($path);

            $this->summary($writer, $report);

            $writer->addNewSheetAndMakeItCurrent();
            $this->ranking($writer, 'Tratamientos', ['N.º', 'Tratamiento', 'Citas'], $report->treatments, false);

            $writer->addNewSheetAndMakeItCurrent();
            $this->ranking($writer, 'Staff', ['N.º', 'Nombre', 'Citas', 'Completadas'], $report->staff, true);

            $writer->addNewSheetAndMakeItCurrent();
            $this->demand($writer, $report->demand);

            $writer->close();
        } catch (\Throwable $e) {
            try {
                $writer->close();
            } catch (\Throwable) {
            }

            @unlink($path);

            throw $e;
        }

        return $path;
    }

    private function summary(Writer $writer, MonthlyReport $report): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Resumen');
        $sheet->setSheetView((new SheetView)->withFreezeRow(5));
        $sheet->setColumnWidth(36, 1);
        $sheet->setColumnWidth(18, 2, 3, 4);

        $month = $report->month;
        $title = (new Style)->withFontBold(true)->withFontSize(14);

        $writer->addRow(new Row([new StringCell('Dentissa · Reporte de '.$month->label(), $title)]));
        $writer->addRow(new Row([new StringCell('Generado el '.$report->activeAsOf->format('d/m/Y H:i').' · Comparado con '.$month->previous()->label())]));
        $writer->addRow(new Row($report->dataUntil === null ? [] : [new StringCell('Mes en curso: datos hasta hoy, '.$report->dataUntil->format('d/m/Y').'.')]));
        $writer->addRow($this->header(['Indicador', $month->optionLabel(), $month->previous()->optionLabel(), 'Diferencia']));

        foreach (self::APPOINTMENT_ROWS as $key => $label) {
            $writer->addRow($this->comparisonRow($label, $report->appointments[$key]));
        }

        foreach (self::RATE_ROWS as $key => $label) {
            $comparison = $report->rates[$key]['comparison'];

            $writer->addRow(new Row([
                new StringCell($label),
                $this->percent($report->rates[$key]['rate']->value),
                $this->percent($comparison->previous),
                $comparison->difference === null ? new StringCell(self::NO_COMPARISON) : new NumericCell($comparison->difference, (new Style)->withFormat(self::SIGNED_POINTS)),
            ]));
        }

        $writer->addRow($this->comparisonRow('Pacientes nuevos', $report->newPatients));
        $writer->addRow(new Row([
            new StringCell('Pacientes activos (al '.$report->activeAsOf->format('d/m/Y').')'),
            new NumericCell($report->activePatients),
            new StringCell(self::NO_COMPARISON),
            new StringCell(self::NO_COMPARISON),
        ]));
    }

    /**
     * @param  list<string>  $headings
     * @param  list<RankedItem>  $items
     */
    private function ranking(Writer $writer, string $name, array $headings, array $items, bool $withExtra): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($name);
        $sheet->setSheetView((new SheetView)->withFreezeRow(2));
        $sheet->setColumnWidth(8, 1);
        $sheet->setColumnWidth(40, 2);
        $sheet->setColumnWidth(14, 3, 4);

        $writer->addRow($this->header($headings));

        foreach ($items as $index => $item) {
            $cells = [new NumericCell($index + 1), new StringCell($item->name), new NumericCell($item->value)];

            if ($withExtra) {
                $cells[] = new NumericCell($item->extra ?? 0);
            }

            $writer->addRow(new Row($cells));
        }
    }

    /**
     * @param  array{hours: list<int>, matrix: list<list<int>>, by_weekday: list<int>, by_hour: list<int>, top_weekdays: list<int>, top_hours: list<int>, total: int}  $demand
     */
    private function demand(Writer $writer, array $demand): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Demanda');
        $sheet->setSheetView((new SheetView)->withFreezeRow(2));
        $sheet->setColumnWidth(10, 1, 2, 3, 4, 5, 6, 7, 8, 9);

        $writer->addRow($this->header(['Hora', ...self::WEEKDAYS, 'Total']));

        if ($demand['hours'] === []) {
            return;
        }

        foreach ($demand['hours'] as $index => $hour) {
            $writer->addRow(new Row([
                new StringCell($hour.':00'),
                ...array_map(fn (int $appointments): Cell => new NumericCell($appointments), $demand['matrix'][$index]),
                new NumericCell($demand['by_hour'][$index]),
            ]));
        }

        $writer->addRow(new Row([
            new StringCell('Total', (new Style)->withFontBold(true)),
            ...array_map(fn (int $appointments): Cell => new NumericCell($appointments), $demand['by_weekday']),
            new NumericCell($demand['total']),
        ]));
    }

    private function comparisonRow(string $label, Comparison $comparison): Row
    {
        return new Row([
            new StringCell($label),
            new NumericCell($comparison->current ?? 0),
            new NumericCell($comparison->previous ?? 0),
            new NumericCell($comparison->difference ?? 0, (new Style)->withFormat(self::SIGNED_COUNT)),
        ]);
    }

    /** A rate as the fraction of its rounded percentage (28.6 → 0.286), or "Sin datos". */
    private function percent(int|float|null $percentage): Cell
    {
        return $percentage === null
            ? new StringCell(self::NO_DATA)
            : new NumericCell(round($percentage / 100, 3), (new Style)->withFormat(self::PERCENT));
    }

    /**
     * @param  list<string>  $headings
     */
    private function header(array $headings): Row
    {
        $style = (new Style)->withFontBold(true)->withBackgroundColor(self::HEADER_BACKGROUND);

        return new Row(array_map(fn (string $heading): Cell => new StringCell($heading, $style), $headings));
    }
}
