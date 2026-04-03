<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

interface PatientRelatedRepository
{
    /**
     * Busca por su ID y devuelve un array con sus datos o un array vacío si no se encuentra.
     */
    public function findByPatientId(PatientId $patientId): ?array;
}
