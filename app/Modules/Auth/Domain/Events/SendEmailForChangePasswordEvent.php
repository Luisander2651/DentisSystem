<?php

namespace App\Modules\Auth\Domain\Events;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Entities\Patient;

class SendEmailForChangePasswordEvent
{
    use Dispatchable, SerializesModels;

    public string $customerEmail;
    public string $customerName;
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Patient $PatientEntity,
        public string $token
   )
    {
        $this->customerName = $PatientEntity->Name()->full();
        $this->customerEmail = $PatientEntity->Email()->value;
    }
}
