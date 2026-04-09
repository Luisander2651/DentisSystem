<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\Entities\PatientRecord;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

interface PatientRecordRepositoryInterface
{
    public function GetByPatientId(PatientId $patientId): ?PatientRecord;
}
