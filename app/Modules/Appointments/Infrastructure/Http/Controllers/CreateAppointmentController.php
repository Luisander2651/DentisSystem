<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\RetriveDataForScheduledAppointmenEventUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;



final readonly class CreateAppointmentController
{
    public function __construct(
        private RetriveDataForScheduledAppointmenEventUseCase $retrieveDataForScheduledAppointmentUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            Log::info('CreateAppointmentController: Iniciando creación de appointment', [
                'user_id' => $request->string('user_id')->value(),
                'patient_id' => $request->string('patient_id')->value(),
                'date' => $request->string('date')->value(),
                'time' => $request->string('time')->value(),
            ]);

            $createAppointmentDTO = CreateAppointmentDTO::create(
                date: $request->string('date')->value(),
                time: $request->string('time')->value(),
                treatmentId: $request->string('treatment_id')->value(),
                userId: $request->string('user_id')->value(),
                patientId: $request->string('patient_id')->value(),
            );

            Log::info('CreateAppointmentController: DTO creado exitosamente');

            $scheduledAppointmentEvent = $this->retrieveDataForScheduledAppointmentUseCase->execute($createAppointmentDTO);

            Log::info('CreateAppointmentController: Evento creado, disparando...', [
                'appointmentId' => $scheduledAppointmentEvent->appointmentEntity->Id()->value,
                'customerPhone' => $scheduledAppointmentEvent->customerPhone,
                'customerName' => $scheduledAppointmentEvent->customerName,
            ]);

            event($scheduledAppointmentEvent);

            Log::info('CreateAppointmentController: Evento disparado exitosamente');

            return response()->json([
                'message' => 'Appointment created successfully',
            ], 201);
        } catch (AppointmentException $e) {
            Log::error('CreateAppointmentController: AppointmentException', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidArgumentException $e) {
            Log::error('CreateAppointmentController: InvalidArgumentException', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ValueObjectsException $e) {
            Log::error('CreateAppointmentController: ValueObjectsException', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            Log::error('CreateAppointmentController: AppointmentAplicationExceptions', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            Log::error('CreateAppointmentController: Exception genérica', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}