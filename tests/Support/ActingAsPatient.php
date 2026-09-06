<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ActingAsPatient
{
    protected function createPatient(array $overrides = []): PatientModel
    {
        return PatientModel::create(array_merge([
            'id' => (string) Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'patient',
        ], $overrides));
    }

    protected function actingAsPatient(array $overrides = []): PatientModel
    {
        $patient = $this->createPatient($overrides);
        $this->actingAs($patient, 'sanctum');

        return $patient;
    }
}
