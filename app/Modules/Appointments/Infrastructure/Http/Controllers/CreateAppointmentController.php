<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentScheduleConflictException;
use App\Modules\Appointments\Aplication\UseCases\RetriveDataForScheduledAppointmenEventUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
            ]);

            event($scheduledAppointmentEvent);

            Log::info('CreateAppointmentController: Evento disparado exitosamente');

            return response()->json([
                'message' => 'Appointment created successfully',
            ], 201);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentScheduleConflictException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
