<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 025 (CA5, CA17): one prescription as the patient reads it, without its ids.
 */
final class PortalPrescriptionResource extends JsonResource
{
    /**
     * @return array{medication: string, dosage: string, daily_frequency: int, duration_days: int, instructions: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'medication' => $this->resource->Medication()->value,
            'dosage' => $this->resource->Dosage()->value,
            'daily_frequency' => $this->resource->DailyFrequency()->value,
            'duration_days' => $this->resource->DurationDays()->value,
            'instructions' => $this->resource->Instructions()?->value,
        ];
    }
}
