<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Aplication\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\UpdateAppointmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateAppointmentController
{
    public function __construct(
        private UpdateAppointmentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $appointmentId = (string) $request->route('id');

            $date = $request->has('date') ? $request->string('date')->value() : null;
            $time = $request->has('time') ? $request->string('time')->value() : null;
            $status = $request->has('status') ? $request->string('status')->value() : null;
            $whatsappReminder = $request->has('whatsapp_reminder') ? $request->boolean('whatsapp_reminder') : null;

            $dto = UpdateAppointmentDTO::create(
                appointmentId: $appointmentId,
                date: $date,
                time: $time,
                whatsappReminder: $whatsappReminder,
                status: $status,
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Appointment updated successfully',
                'data' => $dto->fieldsToUpdate(),
            ], 200);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
