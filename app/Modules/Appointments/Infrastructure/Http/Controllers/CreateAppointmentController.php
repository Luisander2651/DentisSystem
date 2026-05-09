<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\CreateAppointmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;



final readonly class CreateAppointmentController
{
    public function __construct(
        private CreateAppointmentUseCase $createAppointmentUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $createAppointmentDTO = CreateAppointmentDTO::create(
                date: $request->string('date')->value(),
                time: $request->string('time')->value(),
                treatmentId: $request->string('treatment_id')->value(),
                userId: $request->string('user_id')->value(),
                patientId: $request->string('patient_id')->value(),
            );

            $appointment = $this->createAppointmentUseCase->execute($createAppointmentDTO);

            event(new ScheduledAppointment($appointment));

            return response()->json([
                'message' => 'Appointment created successfully',
            ], 201);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}