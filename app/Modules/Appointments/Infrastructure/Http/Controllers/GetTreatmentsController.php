<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Infrastructure\Http\Resources\TreatmentResource;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Illuminate\Http\JsonResponse;

final readonly class GetTreatmentsController
{
    public function __invoke(): JsonResponse
    {
        $treatments = TreatmentModel::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TreatmentResource::collection($treatments),
        ], 200);
    }
}