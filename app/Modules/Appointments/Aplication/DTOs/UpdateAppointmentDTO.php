<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;

final readonly class UpdateAppointmentDTO
{
    public function __construct(
        public string $appointmentId,
        public ?string $date,
        public ?string $time,
        public ?bool $whatsappReminder,
        public ?string $status,
    ) {}

    public static function create(
        string $appointmentId,
        ?string $date,
        ?string $time,
        ?bool $whatsappReminder,
        ?string $status,
    ): self
    {
        return new self(
            appointmentId: $appointmentId,
            date: $date,
            time: $time,
            whatsappReminder: $whatsappReminder,
            status: $status,
        );
    }

    private function hasValue(): bool
    {
        return $this->date !== null ||
            $this->time !== null ||
            $this->whatsappReminder !== null ||
            $this->status !== null;
    }

    public function fieldsToUpdate(): array
    {
        if (!$this->hasValue()) {
            throw AppointmentAplicationExceptions::noInfoProvided();
        }
        $fields = [];

        if ($this->date !== null) {
            $fields['date'] = $this->date;
        }

        if ($this->time !== null) {
            $fields['time'] = $this->time;
        }

        if ($this->whatsappReminder !== null) {
            $fields['whatsapp_reminder'] = $this->whatsappReminder;
        }

        if ($this->status !== null) {
            $fields['status'] = $this->status;
        }

        return $fields;
    }
}