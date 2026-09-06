<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\ValueObjects\Addresses\AddressPatientId;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactEmail;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactInfoPatientId;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\EmergencyContact;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\PhoneNumber;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Allergies;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\BloodType;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\LastDentistVisit;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\MedicalDataPatientId;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Medications;
use Illuminate\Support\Str;

it('Address::update() changes only the provided fields', function () {
    $address = Address::create(
        new AddressPatientId((string) Str::uuid()),
        Street::fromNullable('Main St'),
        City::fromNullable('Springfield'),
        State::fromNullable('IL'),
        PostalCode::fromNullable('62704'),
    );

    $address->update(city: City::fromNullable('Shelbyville'));

    expect($address->Street()->value)->toBe('Main St'); // sin cambio
    expect($address->City()->value)->toBe('Shelbyville');
    expect($address->State()->value)->toBe('IL'); // sin cambio
    expect($address->PostalCode()->value)->toBe('62704'); // sin cambio
});

it('ContactInfo::update() changes only the provided fields', function () {
    $contactInfo = ContactInfo::create(
        new ContactInfoPatientId((string) Str::uuid()),
        PhoneNumber::fromNullable('+1 555 0100'),
        ContactEmail::fromNullable('a@example.com'),
        EmergencyContact::fromNullable('Jane Doe'),
    );

    $contactInfo->update(phoneNumber: PhoneNumber::fromNullable('+1 555 0200'));

    expect($contactInfo->PhoneNumber()->value)->toBe('+1 555 0200');
    expect($contactInfo->ContactEmail()->value)->toBe('a@example.com'); // sin cambio
    expect($contactInfo->EmergencyContact()->value)->toBe('Jane Doe'); // sin cambio
});

it('MedicalData::update() changes only the provided fields', function () {
    $medicalData = MedicalData::create(
        new MedicalDataPatientId((string) Str::uuid()),
        BloodType::fromNullable('O+'),
        Allergies::fromNullableArray(['Penicilina']),
        Medications::fromNullableArray(['Ibuprofeno']),
        LastDentistVisit::fromNullableArray(['2026-01-15']),
    );

    $medicalData->update(bloodType: BloodType::fromNullable('A+'));

    expect($medicalData->BloodType()->value)->toBe('A+');
    expect($medicalData->Allergies()->value)->toBe(['Penicilina']); // sin cambio
    expect($medicalData->Medications()->value)->toBe(['Ibuprofeno']); // sin cambio
});

it('all three default to nullable VOs when created without values', function () {
    $address = Address::create(new AddressPatientId((string) Str::uuid()), Street::fromNullable(null), City::fromNullable(null), State::fromNullable(null), PostalCode::fromNullable(null));
    $contactInfo = ContactInfo::create(new ContactInfoPatientId((string) Str::uuid()), PhoneNumber::fromNullable(null), ContactEmail::fromNullable(null), EmergencyContact::fromNullable(null));
    $medicalData = MedicalData::create(new MedicalDataPatientId((string) Str::uuid()), BloodType::fromNullable(null), Allergies::fromNullableArray(null), Medications::fromNullableArray(null), LastDentistVisit::fromNullableArray(null));

    expect($address->Street()->value)->toBeNull();
    expect($contactInfo->PhoneNumber()->value)->toBeNull();
    expect($medicalData->BloodType()->value)->toBeNull();
});
