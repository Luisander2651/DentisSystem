<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;
use Carbon\CarbonImmutable;
use OpenSpout\Reader\XLSX\Reader;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA12 and TM3: the report and its file carry counts, treatments and staff, and nothing
 * about any patient: no name, contact, health data or id.
 */

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

/** Every text and number of every cell of every sheet, as one string. */
function everyCellOf(string $path): string
{
    $reader = new Reader;
    $reader->open($path);
    $cells = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        $cells[] = $sheet->getName();

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->toArray() as $value) {
                $cells[] = (string) $value;
            }
        }
    }

    $reader->close();

    return implode("\n", $cells);
}

it('leaves every personal and health detail of the patients out of the report and its file (abuse)', function () {
    $patient = $this->createPatient(['first_name' => 'Zacarías', 'last_name' => 'Quintanilla', 'email' => 'zacarias.quintanilla@example.com']);
    ContactInfoModel::create(['patient_id' => $patient->id, 'phone_number' => '5598761234', 'emergency_contact' => 'Rosaura Quintanilla 5511112222', 'email' => 'contacto.quintanilla@example.com']);
    MedicalDataModel::create(['patient_id' => $patient->id, 'blood_type' => 'AB-', 'allergies' => 'Penicilina', 'medications' => 'Metformina', 'last_dentist_visit' => '2026-01-15']);
    $treatment = $this->createTreatment(['name' => 'Limpieza dental de prueba']);
    $doctor = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);

    foreach (['2026-07-06', '2026-07-07', '2026-07-08'] as $index => $date) {
        $this->createAppointment(['date' => $date, 'patient_id' => $patient->id, 'treatment_id' => $treatment->id, 'user_id' => $doctor->id, 'status' => $index === 0 ? 'completada' : 'asignada']);
    }

    $json = $this->getJson($this->monthlyReportUrl('2026-07'))->assertOk()->getContent();
    $response = $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
    $cells = everyCellOf($response->baseResponse->getFile()->getPathname());

    foreach (['Zacarías', 'Quintanilla', 'zacarias.quintanilla@example.com', 'contacto.quintanilla', '5598761234', 'Rosaura', '5511112222', 'Penicilina', 'Metformina', 'AB-', $patient->id] as $detail) {
        expect(mb_stripos($json, $detail))->toBeFalse("the report shows \"$detail\"")
            ->and(mb_stripos($cells, $detail))->toBeFalse("the file shows \"$detail\"");
    }

    expect($json)->toContain('Limpieza dental de prueba')->toContain('Daniela Prueba')
        ->and($cells)->toContain('Limpieza dental de prueba')->toContain('Daniela Prueba');
})->skip('019: pendiente de T041');
