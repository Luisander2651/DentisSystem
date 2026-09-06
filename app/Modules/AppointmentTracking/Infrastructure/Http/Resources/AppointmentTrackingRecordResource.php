<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AppointmentTrackingRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'appointment_tracking' => new AppointmentTrackingResource($this->resource->AppointmentTracking()),
            'prescriptions' => PrescriptionResource::collection($this->resource->Prescriptions()),
        ];
    }
}
