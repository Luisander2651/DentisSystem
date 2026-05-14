<?php

namespace App\Modules\Appointments\Domain\Events;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Entities\Patient;

class ScheduledAppointment
{
    use Dispatchable, SerializesModels;

    public string $appointmentId;
    public string $customerPhone;
    public string $customerName;
    public string $date;
    public string $time;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AppointmentEntity $appointmentEntity,
        public ContactInfo $ContactInfoEntity,
        public Patient $PatientEntity 
   )
    {
        $this->customerPhone = $ContactInfoEntity->PhoneNumber()->value;
        $this->customerName = $PatientEntity->Name()->full();
        $this->date = $appointmentEntity->Date()->value;
        $this->time = $appointmentEntity->Time()->value;
    }
}
