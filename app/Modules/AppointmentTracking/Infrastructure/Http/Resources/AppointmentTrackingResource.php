<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AppointmentTrackingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->Id()->value,
            'appointment_id' => $this->resource->AppointmentId()->value,
            'reason' => $this->resource->Reason()->value,
            'symptoms' => $this->resource->Symptoms()->value,
            'diagnosis' => $this->resource->Diagnosis()->value,
            'procedure_performed' => $this->resource->ProcedurePerformed()->value,
            'observations' => $this->resource->Observations()?->value,
            'recommendations' => $this->resource->Recommendations()?->value,
        ];
    }
}
