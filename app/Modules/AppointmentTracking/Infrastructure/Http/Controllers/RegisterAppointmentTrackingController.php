<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Application\UseCases\RegisterAppointmentTrackingUseCase;
use App\Modules\AppointmentTracking\Domain\Exceptions\TrackingRegistrationException;
use App\Modules\AppointmentTracking\Infrastructure\Http\Requests\RegisterAppointmentTrackingRequest;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\AppointmentTrackingRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Spec 020 (CA8, CA15, A13): 201 with the record; 404 and 409 with the texts of the contract. A
 * refusal leaves through the render of AuthorizationException, with its usual body; any other
 * failure answers the generic 500.
 */
final readonly class RegisterAppointmentTrackingController
{
    public function __construct(
        private RegisterAppointmentTrackingUseCase $useCase,
    ) {}

    public function __invoke(RegisterAppointmentTrackingRequest $request): JsonResponse
    {
        $appointmentId = (string) $request->route('id');

        if (! Str::isUuid($appointmentId)) {
            return response()->json(['error' => TrackingRegistrationException::appointmentNotFound()->getMessage()], 404);
        }

        try {
            $record = $this->useCase->execute(CompleteAppointmentDTO::create(
                appointmentId: $appointmentId,
                reason: $request->string('reason')->value(),
                symptoms: $request->input('symptoms') ?? [],
                diagnosis: $request->string('diagnosis')->value(),
                procedurePerformed: $request->string('procedure_performed')->value(),
                observations: $request->filled('observations') ? $request->string('observations')->value() : null,
                recommendations: $request->filled('recommendations') ? $request->string('recommendations')->value() : null,
                prescriptions: $request->input('prescriptions') ?? [],
            ));

            return response()->json([
                'message' => 'Seguimiento registrado.',
                'data' => new AppointmentTrackingRecordResource($record),
            ], 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (TrackingRegistrationException $e) {
            $status = $e->getMessage() === TrackingRegistrationException::appointmentNotFound()->getMessage() ? 404 : 409;

            return response()->json(['error' => $e->getMessage()], $status);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Revisa los datos del seguimiento e inténtalo de nuevo.'], 422);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
