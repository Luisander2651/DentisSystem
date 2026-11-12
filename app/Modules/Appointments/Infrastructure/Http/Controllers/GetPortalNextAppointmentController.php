<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetPatientNextAppointmentDTO;
use App\Modules\Appointments\Aplication\UseCases\GetPatientNextAppointmentUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\PortalAppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/portal/appointments/next (spec 025, CA1, CA2, CA13): the next appointment of the
 * patient of the session, or null. "Today" is the clinic's date (the application time zone).
 */
final class GetPortalNextAppointmentController
{
    public function __construct(
        private GetPatientNextAppointmentUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $next = $this->useCase->execute(GetPatientNextAppointmentDTO::create(now()->format('Y-m-d')));

            return response()->json([
                'data' => $next === null ? null : new PortalAppointmentResource($next),
            ]);
        } catch (AuthorizationException $e) {
            // The 403 of the whole API, rendered in bootstrap/app.php.
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
