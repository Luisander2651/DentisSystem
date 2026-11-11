<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetPatientAppointmentsDTO;
use App\Modules\Appointments\Aplication\UseCases\GetPatientAppointmentsUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\PortalAppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/portal/appointments (spec 025, CA3, CA4, CA13): the appointments of the patient of
 * the session. It reads nothing from the request: an id sent by the client is ignored.
 */
final class GetPortalAppointmentsController
{
    public function __construct(
        private GetPatientAppointmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $appointments = $this->useCase->execute(GetPatientAppointmentsDTO::create());

            return response()->json([
                'data' => PortalAppointmentResource::collection($appointments),
            ]);
        } catch (AuthorizationException $e) {
            // The 403 of the whole API, rendered in bootstrap/app.php.
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
