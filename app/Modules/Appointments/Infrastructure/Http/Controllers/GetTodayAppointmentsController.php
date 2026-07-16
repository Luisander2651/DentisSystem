<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Aplication\UseCases\GetTodayAppointmentsUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\AppointmentResource;
use Illuminate\Http\JsonResponse;

final readonly class GetTodayAppointmentsController
{
    public function __construct(
        private GetTodayAppointmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $appointments = $this->useCase->execute();

        return response()->json([
            'data' => AppointmentResource::collection($appointments),
        ], 200);
    }
}