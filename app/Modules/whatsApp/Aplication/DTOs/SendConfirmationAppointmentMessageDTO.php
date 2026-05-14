<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Aplication\DTOs;

class SendConfirmationAppointmentMessageDTO
{
    public function __construct(
        public string $customerPhone,
        public string $customerName,
        public string $date,
        public string $time
    ) {
        if (!$this->hasValues()) {
            throw new \InvalidArgumentException('All fields are required.');
        }
    }

    private function hasValues(): bool
    {
        return !empty($this->customerPhone) &&
               !empty($this->customerName) &&
               !empty($this->date) &&
               !empty($this->time);
    }
}