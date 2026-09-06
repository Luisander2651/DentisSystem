<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\AppointmentTracking\Application\DTOs\UpdateAppointmentTrackingDTO;
use App\Modules\AppointmentTracking\Application\Exceptions\AppointmentTrackingApplicationException;
use App\Modules\AppointmentTracking\Application\UseCases\UpdateAppointmentTrackingUseCase;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\AppointmentTrackingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class UpdateAppointmentTrackingController
{
    public function __construct(
        private UpdateAppointmentTrackingUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdateAppointmentTrackingDTO::create(
                appointmentTrackingId: (string) $request->route('id'),
                reason: $request->has('reason') ? $request->string('reason')->value() : null,
                symptoms: $request->has('symptoms') ? $request->input('symptoms') : null,
                diagnosis: $request->has('diagnosis') ? $request->string('diagnosis')->value() : null,
                procedurePerformed: $request->has('procedure_performed') ? $request->string('procedure_performed')->value() : null,
                observations: $request->has('observations') ? $request->string('observations')->value() : null,
                recommendations: $request->has('recommendations') ? $request->string('recommendations')->value() : null,
            );

            $tracking = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Appointment tracking updated successfully',
                'data' => new AppointmentTrackingResource($tracking),
            ], 200);
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
