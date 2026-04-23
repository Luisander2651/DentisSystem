<?php 

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

final readonly class AppointmentWhatsAppReminder
{
    private function __construct(
        private bool $value,
    ) {
    }

    public static function default(): self 
    {
        return new self(false);
    }

    public static function fromBool(bool $value): self
    {
        return new self($value);
    }

    public function value(): bool
    {
        return $this->value;
    }
}