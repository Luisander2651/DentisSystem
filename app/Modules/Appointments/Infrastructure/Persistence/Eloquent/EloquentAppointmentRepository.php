<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Illuminate\Database\Eloquent\Builder;

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

    public function findAllByStatusAndDateOrPatientId(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null, ?AppointmentUserId $userId = null): array
    {
        $query = AppointmentModel::query()->with([
            'user:id,first_name,last_name',
            'patient:id,first_name,last_name',
            'treatment:id,name,time',
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

        if ($userId) {
            $query->where('user_id', $userId->value);
        }

        $appointments = $query->get();

        return $appointments ? $appointments->map(fn ($appointment) => $this->mapToDomain($appointment))->toArray() : [];
    }

    public function delete(AppointmentId $id): void
    {
        AppointmentModel::destroy($id->value);
    }

    /**
     * Spec 020 (CA4, TM8): one limited query for the page and a count() with the same filters.
     */
    public function findUnclosedBefore(AppointmentDate $today, ?AppointmentUserId $userId, int $limit): AppointmentPage
    {
        $query = AppointmentModel::query()
            ->where('date', '<', $today->value)
            ->whereIn('status', [AppointmentStatus::assigned()->value, AppointmentStatus::rescheduled()->value])
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId->value));

        $total = (clone $query)->count();

        $items = $query
            ->with([
                'user:id,first_name,last_name',
                'patient:id,first_name,last_name',
                'treatment:id,name,time',
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->limit($limit)
            ->get()
            ->map(fn (AppointmentModel $appointment): AppointmentEntity => $this->mapToDomain($appointment))
            ->all();

        return AppointmentPage::create($items, $total);
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
            $model->user?->first_name,
            $model->user?->last_name,
            (string) $model->patient?->first_name.' '.$model->patient?->last_name,
            (string) $model->treatment?->time,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString(),
        );
    }
}
