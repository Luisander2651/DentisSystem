<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 025 (CA3, CA17, TM7): an appointment as the patient sees it in the portal. A closed list
 * of fields, without the ids of the patient, the doctor or the treatment.
 */
final class PortalAppointmentResource extends JsonResource
{
    /**
     * @return array{id: string, date: string, time: string, status: string, treatment_name: ?string, doctor_name: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->Id()->value,
            'date' => $this->resource->Date()->value,
            // The contract gives the hour as HH:MM; the database keeps seconds.
            'time' => substr($this->resource->Time()->value, 0, 5),
            'status' => $this->resource->Status()->value,
            'treatment_name' => $this->resource->TreatmentName()?->value,
            'doctor_name' => $this->resource->UserName()?->full(),
        ];
    }
}
