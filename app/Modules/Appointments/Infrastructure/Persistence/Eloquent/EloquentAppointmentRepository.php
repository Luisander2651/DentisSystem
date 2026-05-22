<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;


class EloquentAppointmentRepository implements AppointmentsRepositoryInterface
{
    public function save(AppointmentEntity $appointment): AppointmentEntity
    {
        $appointmentId = $appointment->Id()->value;
        $data = [
            'id' => $appointmentId,
            'date' => $appointment->Date()->value,
            'time' => $appointment->Time()->value,
            'status' => $appointment->Status()->value,
            'whatsapp_reminder' => $appointment->WhatsappReminder(),
            'treatment_id' => $appointment->TreatmentId()->value,
            'user_id' => $appointment->UserId()->value,
            'patient_id' => $appointment->PatientId()->value,
        ];

        $model = AppointmentModel::query()->updateOrCreate(['id' => $appointmentId], $data);

        return $this->mapToDomain($model);
    }

    public function findById(AppointmentId $id): ?AppointmentEntity
    {
        $appointment = AppointmentModel::find($id->value);

        return $appointment ? $this->mapToDomain($appointment) : null;
    }

    public function findAllByStatusAndDateOrPatientId(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null): array
    {
        $query = AppointmentModel::query()->with([
            'user:id,first_name,last_name',
            'patient:id,first_name,last_name',
            'treatment:id,name'
        ]);

        if ($status) {
            $query->where('status', $status->value);
        }

        if ($date) {
            $query->where('date', $date->value);
        }

        if ($patientId) {
            $query->where('patient_id', $patientId->value);
        }


        $appointments = $query->get();

        return $appointments ? $appointments->map(fn($appointment) => $this->mapToDomain($appointment))->toArray() : [];
    }

    public function delete(AppointmentId $id): void
    {
        AppointmentModel::destroy($id->value);
    }
    
    public function mapToDomain(object $model): AppointmentEntity
    {
        return AppointmentEntity::fromPrimitives(
            (string) $model->id,
            (string) $model->date,
            (string) $model->time,
            (bool) $model->whatsapp_reminder,
            (string) $model->status,
            (string) $model->treatment_id,
            (string) $model->user_id,
            (string) $model->patient_id,
            (string) $model->treatment?->name,
            (string) $model->user?->first_name . ' ' . $model->user?->last_name,
            (string) $model->patient?->first_name . ' ' . $model->patient?->last_name,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString(),
        );
    }
}