<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;

function createTestPatientEntity(): Patient
{
    return Patient::create(
        PatientName::create('John', 'Doe'),
        new PatientEmail('john@example.com'),
        PasswordHash::createFromPlainText('password'),
    );
}

it('creates a patient in active status with role patient', function () {
    $patient = createTestPatientEntity();

    expect($patient->Status()->isActive())->toBeTrue();
    expect($patient->Role()->value())->toBe('patient');
    expect($patient->Name()->full())->toBe('John Doe');
});

it('update() changes only the provided fields', function () {
    $patient = createTestPatientEntity();
    $originalEmail = $patient->Email()->value;

    $patient->update(firstName: 'Jane', lastName: null, patientStatus: null);

    expect($patient->Name()->firstName)->toBe('Jane');
    expect($patient->Name()->lastName)->toBe('Doe'); // sin cambio
    expect($patient->Email()->value)->toBe($originalEmail); // Email no es actualizable por update()
    expect($patient->Status()->isActive())->toBeTrue(); // sin cambio
});

it('update() changes status when provided', function () {
    $patient = createTestPatientEntity();

    $patient->update(firstName: null, lastName: null, patientStatus: 'inactive');

    expect($patient->Status()->isInactive())->toBeTrue();
});

it('activate() is a no-op when already active', function () {
    $patient = createTestPatientEntity();
    $updatedAtBefore = $patient->UpdatedAt();

    $patient->activate();

    expect($patient->Status()->isActive())->toBeTrue();
    expect($patient->UpdatedAt())->toEqual($updatedAtBefore);
});

it('deactivate() then activate() round-trips the status', function () {
    $patient = createTestPatientEntity();

    $patient->deactivate();
    expect($patient->Status()->isInactive())->toBeTrue();

    $patient->activate();
    expect($patient->Status()->isActive())->toBeTrue();
});

it('passwordMatches() validates against the current hash', function () {
    $patient = createTestPatientEntity();

    expect($patient->passwordMatches('password'))->toBeTrue();
    expect($patient->passwordMatches('wrong'))->toBeFalse();
});

it('changePassword() replaces the hash', function () {
    $patient = createTestPatientEntity();

    $patient->changePassword(PasswordHash::createFromPlainText('new-password'));

    expect($patient->passwordMatches('new-password'))->toBeTrue();
    expect($patient->passwordMatches('password'))->toBeFalse();
});

it('changeName() replaces the name', function () {
    $patient = createTestPatientEntity();

    $patient->changeName(PatientName::create('Alice', 'Smith'));

    expect($patient->Name()->full())->toBe('Alice Smith');
});
