<?php

declare(strict_types=1);

namespace Tests\Modules\Patients\Integration;

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\AddressesModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

abstract class PatientsIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use RefreshDatabase;

    protected function createAddress(array $overrides = []): AddressesModel
    {
        $overrides['patient_id'] ??= $this->createPatient()->id;

        return AddressesModel::create(array_merge([
            'street' => 'Av. Siempre Viva 742',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62704',
        ], $overrides));
    }

    protected function createContactInfo(array $overrides = []): ContactInfoModel
    {
        $overrides['patient_id'] ??= $this->createPatient()->id;

        return ContactInfoModel::create(array_merge([
            'phone_number' => '+1 555 0100',
            'email' => 'contact@example.com',
            'emergency_contact' => 'Jane Doe',
        ], $overrides));
    }

    protected function createMedicalData(array $overrides = []): MedicalDataModel
    {
        $overrides['patient_id'] ??= $this->createPatient()->id;

        return MedicalDataModel::create(array_merge([
            'blood_type' => 'O+',
            'allergies' => ['Penicilina'],
            'medications' => ['Ibuprofeno'],
            'last_dentist_visit' => ['2026-01-15'],
        ], $overrides));
    }

    protected function patientsUrl(): string
    {
        return '/api/v1/patients';
    }

    protected function patientUrl(string $id): string
    {
        return "/api/v1/patients/{$id}";
    }

    protected function addressUrl(string $patientId): string
    {
        return "/api/v1/patients/{$patientId}/address";
    }

    protected function contactInfoUrl(string $patientId): string
    {
        return "/api/v1/patients/{$patientId}/contact-info";
    }

    protected function medicalDataUrl(string $patientId): string
    {
        return "/api/v1/patients/{$patientId}/medical-data";
    }

    protected function patientRecordUrl(string $patientId): string
    {
        return "/api/v1/patients/{$patientId}/record";
    }

    protected function validCreatePatientPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => Str::uuid().'@example.com',
            'password' => 'Sup3rSecret!',
        ], $overrides);
    }

    protected function validCreateAddressPayload(array $overrides = []): array
    {
        return array_merge([
            'street' => 'Av. Siempre Viva 742',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62704',
        ], $overrides);
    }

    protected function validCreateContactInfoPayload(array $overrides = []): array
    {
        return array_merge([
            'phone_number' => '+1 555 0100',
            'contact_email' => 'contact@example.com',
            'emergency_contact' => 'Jane Doe',
        ], $overrides);
    }

    protected function validCreateMedicalDataPayload(array $overrides = []): array
    {
        return array_merge([
            'blood_type' => 'O+',
            'allergies' => ['Penicilina'],
            'medications' => ['Ibuprofeno'],
            'last_dentist_visit' => ['2026-01-15'],
        ], $overrides);
    }
}
