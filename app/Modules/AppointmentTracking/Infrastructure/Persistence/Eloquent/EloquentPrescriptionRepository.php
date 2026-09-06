<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent;

use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\PrescriptionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;

class EloquentPrescriptionRepository implements PrescriptionRepositoryInterface
{
    public function save(PrescriptionEntity $prescription): PrescriptionEntity
    {
        $id = $prescription->Id()->value;
        $data = [
            'id' => $id,
            'medication' => $prescription->Medication()->value,
            'dosage' => $prescription->Dosage()->value,
            'duration_days' => $prescription->DurationDays()->value,
            'daily_frequency' => $prescription->DailyFrequency()->value,
            'instructions' => $prescription->Instructions()?->value,
            'appointment_tracking_id' => $prescription->AppointmentTrackingId()->value,
        ];

        $model = AppointmentTrackingPrescriptionModel::query()->updateOrCreate(['id' => $id], $data);

        return $this->mapToDomain($model);
    }

    public function findById(PrescriptionId $id): ?PrescriptionEntity
    {
        $model = AppointmentTrackingPrescriptionModel::find($id->value);

        return $model ? $this->mapToDomain($model) : null;
    }

    public function findAllByAppointmentTrackingId(PrescriptionAppointmentTrackingId $appointmentTrackingId): array
    {
        $models = AppointmentTrackingPrescriptionModel::query()
            ->where('appointment_tracking_id', $appointmentTrackingId->value)
            ->get();

        return $models->map(fn ($model) => $this->mapToDomain($model))->toArray();
    }

    public function delete(PrescriptionId $id): void
    {
        AppointmentTrackingPrescriptionModel::destroy($id->value);
    }

    private function mapToDomain(object $model): PrescriptionEntity
    {
        return PrescriptionEntity::fromPrimitives(
            (string) $model->id,
            (string) $model->appointment_tracking_id,
            (string) $model->medication,
            (string) $model->dosage,
            (int) $model->duration_days,
            (int) $model->daily_frequency,
            $model->instructions !== null ? (string) $model->instructions : null,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString(),
        );
    }
}
