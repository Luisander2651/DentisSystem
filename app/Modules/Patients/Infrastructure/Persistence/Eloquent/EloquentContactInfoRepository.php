<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;

class EloquentContactInfoRepository implements ContactInfoRepositoryInterface
{
    public function save(ContactInfo $contactInfo): void
    {
        $data = [
            'patient_id' => $contactInfo->PatientId()->value,
            'phone_number' => $contactInfo->PhoneNumber()->value,
            'email' => $contactInfo->ContactEmail()->value,
            'emergency_contact' => $contactInfo->EmergencyContact()->value,
        ];

        ContactInfoModel::updateOrCreate(['id' => $contactInfo->Id()?->value], $data);
    }

    public function findByPatientId(PatientId $patientId): ?ContactInfo
    {
        $contactInfo = ContactInfoModel::where('patient_id', $patientId->value)->first();

        return $contactInfo ? $this->mapToDomain($contactInfo) : null;
    }

    public function deleteByPatientId(PatientId $patientId): void
    {
        ContactInfoModel::where('patient_id', $patientId->value)->delete();
    }

    private function mapToDomain(object $model): ContactInfo
    {
        return ContactInfo::fromPrimitives(
            (int) $model->id,
            (string) $model->patient_id,
            $model->phone_number,
            $model->email,
            $model->emergency_contact,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString()
        );
    }
}
