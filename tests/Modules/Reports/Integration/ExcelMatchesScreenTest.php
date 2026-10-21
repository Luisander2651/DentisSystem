<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA8 and the requirement that the screen and the file show the same figures (P13):
 * every figure of the JSON of July 2026 is the one in its cell of the file.
 */

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

/**
 * @return array<string, list<list<mixed>>>
 */
function matchedWorkbook(string $path): array
{
    $reader = new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));
    $reader->open($path);
    $sheets = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheets[$sheet->getName()][] = $row->toArray();
        }
    }

    $reader->close();

    return $sheets;
}

it('writes in the file the same figures the screen shows', function () {
    $this->seedJuly2026();
    $this->createAppointment(['date' => '2026-06-10', 'status' => 'completada']);

    $json = $this->getJson($this->monthlyReportUrl('2026-07'))->assertOk()->json('data');
    $response = $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
    $workbook = matchedWorkbook($response->baseResponse->getFile()->getPathname());
    $summary = $workbook['Resumen'];

    $rows = ['total' => 4, 'completed' => 5, 'cancelled' => 6, 'unclosed' => 7, 'rescheduled_pending' => 8, 'assigned_pending' => 9];

    foreach ($rows as $key => $row) {
        expect(array_slice($summary[$row], 1))->toEqual([
            $json['appointments'][$key]['value'],
            $json['appointments'][$key]['previous'],
            $json['appointments'][$key]['difference'],
        ]);
    }

    foreach (['attendance' => 10, 'cancellation' => 11] as $key => $row) {
        $rate = $json['rates'][$key];

        expect($summary[$row][1])->toEqual($rate['value'] === null ? 'Sin datos' : round($rate['value'] / 100, 3))
            ->and($summary[$row][2])->toEqual($rate['previous'] === null ? 'Sin datos' : round($rate['previous'] / 100, 3))
            ->and($summary[$row][3])->toEqual($rate['difference'] ?? '—');
    }

    expect(array_slice($summary[12], 1))->toEqual([$json['patients']['new']['value'], $json['patients']['new']['previous'], $json['patients']['new']['difference']])
        ->and($summary[13][1])->toEqual($json['patients']['active']['value'])
        ->and(array_map(fn (array $row): array => [$row[1], $row[2]], array_slice($workbook['Tratamientos'], 1)))
        ->toEqual(array_map(fn (array $item): array => [$item['name'], $item['appointments']], $json['treatments']))
        ->and(array_map(fn (array $row): array => [$row[1], $row[2], $row[3]], array_slice($workbook['Staff'], 1)))
        ->toEqual(array_map(fn (array $item): array => [$item['name'], $item['appointments'], $item['completed']], $json['staff']))
        ->and(array_map(fn (array $row): array => array_slice($row, 1, 7), array_slice($workbook['Demanda'], 1, -1)))
        ->toEqual($json['demand']['matrix'])
        ->and(array_slice(end($workbook['Demanda']), 1, 7))->toEqual($json['demand']['by_weekday'])
        ->and(end($workbook['Demanda'])[8])->toEqual($json['demand']['total']);
});
