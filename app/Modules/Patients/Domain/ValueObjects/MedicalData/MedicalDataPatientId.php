<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Core\Domain\UuidIdentifier;
use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\MedicalDataPatientIdException;

final readonly class MedicalDataPatientId extends UuidIdentifier
{}

