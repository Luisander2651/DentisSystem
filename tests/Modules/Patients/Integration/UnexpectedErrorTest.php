<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\PatientRecordRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 014, CA12 and CA13: an unexpected error answers a generic 500 and is logged once as
 * `unexpected_error`, without the exception message - which may carry patient data, as the
 * bindings of a failed query or the value inside a domain exception do.
 */
const PATIENTS_ERROR_TEST_PHONE = '+52 555 010 9999';
const PATIENTS_ERROR_TEST_ALLERGY = 'Penicilina-DatoDePrueba';
const PATIENTS_ERROR_TEST_EMAIL = 'dato-de-prueba@example.com';

beforeEach(function () {
    $this->logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $this->logged->push($entry));
});

function patientsLoggedText(Collection $logged): string
{
    return $logged
        ->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))
        ->implode("\n");
}

it('answers a generic 500 when reading a patient fails unexpectedly', function () {
    $this->actingAsAdmin();
    $patientId = $this->createPatient()->id;
    $this->mock(PatientsRepositoryInterface::class)
        ->shouldReceive('findByPatientId')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson($this->patientUrl($patientId))
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when updating contact info fails unexpectedly', function () {
    $this->actingAsAdmin();
    $patientId = $this->createPatient()->id;
    $this->mock(ContactInfoRepositoryInterface::class)
        ->shouldReceive('findByPatientId')->andThrow(new RuntimeException('detalle interno'));

    $this->putJson($this->contactInfoUrl($patientId), ['emergency_contact' => 'John Roe'])
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('logs a failed query without the patient data carried in its bindings', function () {
    $this->actingAsAdmin();
    $patientId = $this->createPatient()->id;
    $this->mock(PatientsRepositoryInterface::class)
        ->shouldReceive('findByPatientId')
        ->andThrow(new QueryException(
            'pgsql',
            'select * from contact_info where phone_number = ? and allergies = ?',
            [PATIENTS_ERROR_TEST_PHONE, PATIENTS_ERROR_TEST_ALLERGY],
            new PDOException('SQLSTATE[08006] connection failure'),
        ));

    $this->getJson($this->patientUrl($patientId))->assertStatus(500);

    $unexpected = $this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error');

    expect($unexpected)->toHaveCount(1)
        ->and($unexpected->first()->context)->toHaveKeys(['exception', 'origin'])
        ->and(patientsLoggedText($this->logged))->not->toContain(PATIENTS_ERROR_TEST_PHONE)
        ->and(patientsLoggedText($this->logged))->not->toContain(PATIENTS_ERROR_TEST_ALLERGY);
});

it('logs an unexpected exception without the patient data carried in its message', function () {
    $this->actingAsAdmin();
    $patientId = $this->createPatient()->id;
    $this->mock(PatientRecordRepositoryInterface::class)
        ->shouldReceive('GetByPatientId')
        ->andThrow(new RuntimeException('The email '.PATIENTS_ERROR_TEST_EMAIL.' is already in use by another patient.'));

    $this->getJson($this->patientRecordUrl($patientId))
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);

    $unexpected = $this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error');

    expect($unexpected)->toHaveCount(1)
        ->and($unexpected->first()->context)->toHaveKeys(['exception', 'origin'])
        ->and(patientsLoggedText($this->logged))->not->toContain(PATIENTS_ERROR_TEST_EMAIL);
});
