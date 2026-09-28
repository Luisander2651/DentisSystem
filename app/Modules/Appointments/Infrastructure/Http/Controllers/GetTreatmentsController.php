<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetTreatmentsDTO;
use App\Modules\Appointments\Aplication\UseCases\GetTreatmentsUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\TreatmentResource;
use Illuminate\Http\JsonResponse;

/**
 * Treatments catalog for the appointment form of the agenda. Spec 014: it goes through
 * GetTreatmentsUseCase (treatments.view, administrator only) instead of querying Eloquent.
 */
final readonly class GetTreatmentsController
{
    public function __construct(
        private GetTreatmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $treatments = $this->useCase->execute(GetTreatmentsDTO::create());

            return response()->json([
                'data' => TreatmentResource::collection($treatments),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
