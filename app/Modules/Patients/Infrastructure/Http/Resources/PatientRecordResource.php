<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PatientRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $patient = $this->resource->GetPatient();
        $contactInfo = $this->resource->GetContactInfo();
        $address = $this->resource->GetAddress();
        $medicalData = $this->resource->GetMedicalData();

        return [
            'patient' => [
                'id' => $patient->Id()->value,
                'first_name' => $patient->Name()->firstName,
                'last_name' => $patient->Name()->lastName,
                'email' => $patient->Email()->value,
                'status' => $patient->Status()->value,
                'role' => $patient->Role()->value,
            ],
            'contact_info' => $contactInfo ? [
                'id' => $contactInfo->Id()?->value,
                'patient_id' => $contactInfo->PatientId()->value,
                'phone_number' => $contactInfo->PhoneNumber()->value,
                'contact_email' => $contactInfo->ContactEmail()->value,
                'emergency_contact' => $contactInfo->EmergencyContact()->value,
            ] : null,
            'address' => $address ? [
                'id' => $address->Id()?->value,
                'patient_id' => $address->PatientId()->value,
                'street' => $address->Street()->value,
                'city' => $address->City()->value,
                'state' => $address->State()->value,
                'postal_code' => $address->PostalCode()->value,
            ] : null,
            'medical_data' => $medicalData ? [
                'id' => $medicalData->Id()?->value,
                'patient_id' => $medicalData->PatientId()->value,
                'blood_type' => $medicalData->BloodType()->value,
                'allergies' => $medicalData->Allergies()->value,
                'medications' => $medicalData->Medications()->value,
                'last_dentist_visit' => $medicalData->LastDentistVisit()->value,
            ] : null,
        ];
    }
}
