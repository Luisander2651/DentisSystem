<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\UseCases\CreateAppointmentUseCase;
use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class RetriveDataForScheduledAppointmenEventUseCase
{
    public function __construct(
        private CreateAppointmentUseCase $createAppointmentUseCase,
        private PatientsRepositoryInterface $patientsRepository,
        private ContactInfoRepositoryInterface $contactInfoRepository,
    ) {}

    public function execute(CreateAppointmentDTO $createAppointmentDTO): ScheduledAppointment
    {
        Log::info('RetriveDataForScheduledAppointmenEventUseCase: Iniciando', [
            'patientId' => $createAppointmentDTO->patientId,
        ]);

        // Crear la cita
        $appointment = $this->createAppointmentUseCase->execute($createAppointmentDTO);
        Log::info('RetriveDataForScheduledAppointmenEventUseCase: Appointment creado', [
            'appointmentId' => $appointment->Id()->value,
        ]);

        // Obtener datos del paciente
        $patient = $this->patientsRepository->findByPatientId($appointment->PatientId());
        if ($patient === null) {
            Log::warning('RetriveDataForScheduledAppointmenEventUseCase: Paciente no encontrado', [
                'patientId' => $appointment->PatientId()->value,
            ]);
        } else {
            Log::info('RetriveDataForScheduledAppointmenEventUseCase: Paciente encontrado', [
                'patientId' => $patient->Id()->value,
            ]);
        }
        
        // Obtener información de contacto del paciente
        $contactInfo = $this->contactInfoRepository->findByPatientId($appointment->PatientId());
        if ($contactInfo === null) {
            Log::warning('RetriveDataForScheduledAppointmenEventUseCase: ContactInfo no encontrado', [
                'patientId' => $appointment->PatientId()->value,
            ]);
        } else {
            Log::info('RetriveDataForScheduledAppointmenEventUseCase: ContactInfo encontrado', [
                'patientId' => $appointment->PatientId()->value,
                'phone' => $contactInfo->PhoneNumber()?->value ?? 'null',
            ]);
        }

        // Retornar el evento con toda la información
        return new ScheduledAppointment(
            appointmentEntity: $appointment,
            ContactInfoEntity: $contactInfo,
            PatientEntity: $patient
        );
    }
}