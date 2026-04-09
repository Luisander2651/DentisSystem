<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;

class EloquentMedicalDataRepository implements MedicalDataRepositoryInterface
{
    public function save(MedicalData $medicalData): void
    {
        $data = [
            'patient_id' => $medicalData->PatientId()->value,
            'blood_type' => $medicalData->BloodType()->value,
            'allergies' => $medicalData->Allergies()->value,
            'medications' => $medicalData->Medications()->value,
            'last_dentist_visit' => $medicalData->LastDentistVisit()->value,
        ];

        MedicalDataModel::updateOrCreate(['id' => $medicalData->Id()?->value], $data);
    }

    public function findByPatientId(PatientId $patientId): ?MedicalData
    {
        $medicalData = MedicalDataModel::where('patient_id', $patientId->value)->first();

        return $medicalData ? $this->mapToDomain($medicalData) : null;
    }

    public function deleteByPatientId(PatientId $patientId): void
    {
        MedicalDataModel::where('patient_id', $patientId->value)->delete();
    }

    private function mapToDomain(object $model): MedicalData
    {
        return MedicalData::fromPrimitives(
            (int) $model->id,
            (string) $model->patient_id,
            $model->blood_type,
            $this->toNullableArray($model->allergies),
            $this->toNullableArray($model->medications),
            $this->toNullableArray($model->last_dentist_visit),
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString()
        );
    }

    private function toNullableArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
