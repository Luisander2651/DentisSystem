<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Patients\Aplication\DTOs\GetPatientsByStatusDTO;
use App\Modules\Patients\Aplication\UseCases\GetPatientsByStatusUseCase;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientResource;
use Illuminate\Http\JsonResponse;

final readonly class GetPatientsForAppointmentSelectController
{
    public function __construct(
        private GetPatientsByStatusUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $patients = $this->useCase->execute(GetPatientsByStatusDTO::create());

        return response()->json([
            'data' => PatientResource::collection($patients),
        ], 200);
    }
}