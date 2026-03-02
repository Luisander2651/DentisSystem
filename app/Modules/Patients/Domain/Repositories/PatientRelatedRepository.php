<?php 

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Appointments\Domain\Entities\Patient;

// object de patientid y despues solo extender una sola 
// fuincion de la interface y al finalkizar iumplementar 
// las nuevas funciones en los demas repositorios

interface PatientRelatedRepository
{
    /**
     * Busca por su ID y devuelve un array con sus datos o un array vacío si no se encuentra
     * @param PatientId $patientId El ID del paciente a buscar
     * @return Patient[] Un array con los datos del paciente o un array vacío si no se encuentra
     * @return Appointment[] Un array con las citas del paciente o un array vacío si no se encuentra
     * @return MedicalData[] Un array con los historiales médicos del paciente o un array vacío si no se encuentra
     * @return Address[] Un array con las direcciones del paciente o un array vacío si no se encuentra
     */
    public function findByPatientId(PatientId $patientId): ?array;
}
