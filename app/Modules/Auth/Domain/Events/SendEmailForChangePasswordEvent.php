<?php

namespace App\Modules\Auth\Domain\Events;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Users\Domain\Entities\UserEntity;
class SendEmailForChangePasswordEvent
{
    use Dispatchable, SerializesModels;

    public string $customerEmail;
    public string $customerName;
    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?Patient $PatientEntity,
        public ?UserEntity $UserEntity,
        public string $token
   )
    {
        $this->customerName = $PatientEntity ? $PatientEntity->Name()->full() : $UserEntity->Name()->full();
        $this->customerEmail = $PatientEntity ? $PatientEntity->Email()->value : $UserEntity->Email()->value;
    }
}
