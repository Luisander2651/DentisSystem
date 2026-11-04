<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\GetInternalTodayAppointmentsUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\AppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/internal/appointments/today: the clinic day for the attendance microservice.
 */
final readonly class GetInternalTodayAppointmentsController
{
    public function __construct(
        private GetInternalTodayAppointmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $appointments = $this->useCase->execute();

            return response()->json([
                'data' => AppointmentResource::collection($appointments),
            ], 200);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
