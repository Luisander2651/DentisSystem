<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\AppointmentTracking\Application\DTOs\GetAppointmentTrackingByAppointmentIdDTO;
use App\Modules\AppointmentTracking\Application\UseCases\GetPatientPrescriptionsUseCase;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\PortalPrescriptionsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * GET /api/v1/portal/appointments/{id}/prescriptions (spec 025, CA5, CA6, CA8, CA12): the
 * recommendations and prescriptions of an appointment of the patient. Someone else's appointment
 * answers 404 like a missing one; the no-store header comes from the portal group.
 */
final class GetPortalPrescriptionsController
{
    public function __construct(
        private GetPatientPrescriptionsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute(
                GetAppointmentTrackingByAppointmentIdDTO::create((string) $request->route('id'))
            );

            if ($result === null) {
                return response()->json(['error' => 'Appointment not found.'], 404);
            }

            return response()->json(['data' => new PortalPrescriptionsResource($result)]);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'Invalid appointment id.'], 400);
        } catch (AuthorizationException $e) {
            // The 403 of the whole API, rendered in bootstrap/app.php.
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
