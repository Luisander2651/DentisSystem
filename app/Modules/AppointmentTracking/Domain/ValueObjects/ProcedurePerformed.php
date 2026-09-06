<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\ProcedurePerformedException;

final readonly class ProcedurePerformed
{
    private function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw ProcedurePerformedException::emptyProcedurePerformed();
        }
    }

    public static function create(string $procedurePerformed): self
    {
        return new self($procedurePerformed);
    }

    public static function fromString(string $procedurePerformed): self
    {
        return new self($procedurePerformed);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
