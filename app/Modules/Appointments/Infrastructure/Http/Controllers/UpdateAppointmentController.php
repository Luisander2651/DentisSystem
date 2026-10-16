<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentScheduleConflictException;
use App\Modules\Appointments\Aplication\Exceptions\RescheduleAndStatusTogetherException;
use App\Modules\Appointments\Aplication\UseCases\UpdateAppointmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\AppointmentInThePastException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use App\Modules\Appointments\Infrastructure\Http\Requests\UpdateAppointmentRequest;
use Illuminate\Http\JsonResponse;

final class UpdateAppointmentController
{
    public function __construct(
        private UpdateAppointmentUseCase $useCase,
    ) {}

    public function __invoke(UpdateAppointmentRequest $request): JsonResponse
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
        } catch (AppointmentInThePastException $e) {
            return self::invalid('date', $e->getMessage());
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (RescheduleAndStatusTogetherException $e) {
            return self::invalid('status', $e->getMessage());
        } catch (AppointmentAplicationExceptions $e) {
            // Spec 017: the empty body keeps its 409 (decisión del usuario, /analyze A24).
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AppointmentScheduleConflictException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }

    /** Spec 017 (CA5): in the format of a validation error, which the dialog shows as it comes. */
    private static function invalid(string $field, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }
}
