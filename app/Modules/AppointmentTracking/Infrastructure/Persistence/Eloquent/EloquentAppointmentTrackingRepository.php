<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent;

use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;

class EloquentAppointmentTrackingRepository implements AppointmentTrackingRepositoryInterface
{
    public function save(AppointmentTrackingEntity $appointmentTracking): AppointmentTrackingEntity
    {
        $id = $appointmentTracking->Id()->value;
        $data = [
            'id' => $id,
            'reason' => $appointmentTracking->Reason()->value,
            'symptoms' => $appointmentTracking->Symptoms()->value,
            'diagnosis' => $appointmentTracking->Diagnosis()->value,
            'procedure_performed' => $appointmentTracking->ProcedurePerformed()->value,
            'observations' => $appointmentTracking->Observations()?->value,
            'recommendations' => $appointmentTracking->Recommendations()?->value,
            'appointment_id' => $appointmentTracking->AppointmentId()->value,
        ];

        $model = AppointmentTrackingModel::query()->updateOrCreate(['id' => $id], $data);

        return $this->mapToDomain($model);
    }

    public function findById(AppointmentTrackingId $id): ?AppointmentTrackingEntity
    {
        $model = AppointmentTrackingModel::find($id->value);

        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByAppointmentId(AppointmentTrackingAppointmentId $appointmentId): ?AppointmentTrackingEntity
    {
        $model = AppointmentTrackingModel::query()
            ->where('appointment_id', $appointmentId->value)
            ->first();

        return $model ? $this->mapToDomain($model) : null;
    }

    public function delete(AppointmentTrackingId $id): void
    {
        AppointmentTrackingModel::destroy($id->value);
    }

    private function mapToDomain(object $model): AppointmentTrackingEntity
    {
        return AppointmentTrackingEntity::fromPrimitives(
            (string) $model->id,
            (string) $model->appointment_id,
            (string) $model->reason,
            (array) $model->symptoms,
            (string) $model->diagnosis,
            (string) $model->procedure_performed,
            $model->observations !== null ? (string) $model->observations : null,
            $model->recommendations !== null ? (string) $model->recommendations : null,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString(),
        );
    }
}
