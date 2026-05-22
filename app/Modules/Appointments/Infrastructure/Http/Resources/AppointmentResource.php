<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;


final class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Id()->value,
            'date' => $this->Date()->value,
            'time' => $this->Time()->value,
            'whatsapp_reminder' => $this->WhatsappReminder(),
            'status' => $this->Status()->value,
            'treatment_id' => $this->TreatmentId()->value,
            'user_id' => $this->UserId()->value,
            'patient_id' => $this->PatientId()->value,
            'treatment_name' => $this->TreatmentName()?->value,
            'user_name' => $this->UserName()?->full(),
            'patient_name' => $this->PatientName()?->full(),
        ];
    }
}