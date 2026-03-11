<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Modules\Patients\Domain\Repositories\PatientRepositoryInterface;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientRole;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;

class EloquentPatientRepository implements PatientRepositoryInterface
{
    public function findByEmail(string $email): ?Patient
    {
        $patient = PatientModel::where('email', $email)->first();

        return $patient ? $this->mapToDomain($patient) : null;
    }

    public function findByPatientId(PatientId $patientId): ?array
    {
        $patient = PatientModel::find($patientId->value);

        return $patient ? $this->mapToDomain($patient) : null;
    }

    public function save(Patient $patient): void
    {
        $data = [
            'first_name' => $patient->Name()->firstName,
            'last_name' => $patient->Name()->lastName,
            'email' => $patient->Email()->value,
            'password' => $patient->PasswordHash()->value,
            'status' => $patient->Status()->value,
            'role' => $patient->Role()->value,
        ];

        PatientModel::updateOrCreate(['id' => $patient->Id()->value], $data);
    }

    public function delete(PatientId $patientId): void
    {
        PatientModel::destroy($patientId->value);
    }

    public function findByRoleAndStatus(?PatientRole $role, ?PatientStatus $status): ?array
    {
        $query = PatientModel::query();

        if ($role !== null) {
            $query->where('role', $role->value);
        }

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        $patients = $query->get();

        return $patients ? $patients->map(fn($patient) => $this->mapToDomain($patient))->toArray() : [];
    }

    private function mapToDomain(PatientModel $model): Patient
    {
        // Mapear el modelo de Eloquent a la entidad de dominio
        return Patient::fromPrimitives(
            (string) $model->id,
            (string) $model->first_name,
            (string) $model->last_name,
            (string) $model->email,
            (string) $model->password,
            (string) $model->status,
            (string) $model->role,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString()
        );
    }
}
