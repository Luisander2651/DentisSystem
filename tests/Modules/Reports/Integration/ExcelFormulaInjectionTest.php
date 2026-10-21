<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA13 and TM4 (ADR 0005): a treatment or a member of the staff whose name starts with
 * =, +, - or @ is written as plain text, never as a formula a spreadsheet would run.
 */

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

/**
 * The XML of every worksheet of the file, by its name in the archive.
 *
 * @return array<string, string>
 */
function worksheetsXml(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);
    $sheets = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = (string) $zip->getNameIndex($index);

        if (str_starts_with($name, 'xl/worksheets/') && str_ends_with($name, '.xml')) {
            $sheets[$name] = (string) $zip->getFromName($name);
        }
    }

    $zip->close();

    return $sheets;
}

/** The type and the text of the cell of a worksheet whose text is the given one. */
function cellHolding(string $xml, string $text): ?array
{
    $sheet = simplexml_load_string($xml);
    $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    foreach ($sheet->xpath('//x:c') as $cell) {
        $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $value = implode('', array_map('strval', $cell->xpath('.//x:t')));

        if ($value === $text) {
            return ['type' => (string) $cell['t'], 'text' => $value];
        }
    }

    return null;
}

it('writes names that look like formulas as plain text, with no formula in the file (abuse)', function () {
    $hyperlink = $this->createTreatment(['name' => '=HYPERLINK("http://x","y")']);
    $minus = $this->createTreatment(['name' => '-1+1']);
    $plus = $this->createUserWithRole('doctor', ['first_name' => '+cmd', 'last_name' => 'Prueba']);
    $at = $this->createUserWithRole('doctor', ['first_name' => '@SUM(A1)', 'last_name' => 'Prueba']);
    $this->createAppointment(['date' => '2026-07-06', 'treatment_id' => $hyperlink->id, 'user_id' => $plus->id]);
    $this->createAppointment(['date' => '2026-07-07', 'treatment_id' => $minus->id, 'user_id' => $at->id]);

    $response = $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
    $sheets = worksheetsXml($response->baseResponse->getFile()->getPathname());

    foreach ($sheets as $name => $xml) {
        expect($xml)->not->toContain('<f>')->not->toContain('<f ', "$name holds a formula");
    }

    foreach ([
        'xl/worksheets/sheet2.xml' => ['=HYPERLINK("http://x","y")', '-1+1'],
        'xl/worksheets/sheet3.xml' => ['+cmd Prueba', '@SUM(A1) Prueba'],
    ] as $sheet => $names) {
        foreach ($names as $text) {
            expect(cellHolding($sheets[$sheet], $text))->toBe(['type' => 'inlineStr', 'text' => $text]);
        }
    }
})->skip('019: pendiente de T041');
