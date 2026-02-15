<?php 

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

// object de patientid y despues solo extender una sola 
// fuincion de la interface y al finalkizar iumplementar 
// las nuevas funciones en los demas repositorios

interface PatientRelatedRepository
{
    public function findByPatientId(PatientId $patientId): array;
}
