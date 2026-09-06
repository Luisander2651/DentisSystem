<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PrescriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->Id()->value,
            'appointment_tracking_id' => $this->resource->AppointmentTrackingId()->value,
            'medication' => $this->resource->Medication()->value,
            'dosage' => $this->resource->Dosage()->value,
            'duration_days' => $this->resource->DurationDays()->value,
            'daily_frequency' => $this->resource->DailyFrequency()->value,
            'instructions' => $this->resource->Instructions()?->value,
        ];
    }
}
