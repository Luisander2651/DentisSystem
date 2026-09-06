<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Application\Exceptions\AppointmentTrackingApplicationException;
use App\Modules\AppointmentTracking\Application\UseCases\CompleteAppointmentUseCase;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\AppointmentTrackingRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class CompleteAppointmentController
{
    public function __construct(
        private CompleteAppointmentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = CompleteAppointmentDTO::create(
                appointmentId: (string) $request->route('id'),
                reason: $request->string('reason')->value(),
                symptoms: $request->input('symptoms', []),
                diagnosis: $request->string('diagnosis')->value(),
                procedurePerformed: $request->string('procedure_performed')->value(),
                observations: $request->has('observations') ? $request->string('observations')->value() : null,
                recommendations: $request->has('recommendations') ? $request->string('recommendations')->value() : null,
                prescriptions: $request->input('prescriptions', []),
            );

            $record = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Appointment completed successfully',
                'data' => new AppointmentTrackingRecordResource($record),
            ], 201);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AppointmentTrackingException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AppointmentTrackingApplicationException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
