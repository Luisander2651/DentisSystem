<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\GetAgendaTreatmentsUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\TreatmentResource;
use Illuminate\Http\JsonResponse;

/**
 * Treatments catalog for the appointment form of the agenda. Spec 018 (D11): it goes through
 * GetAgendaTreatmentsUseCase (agenda.selectors.view, administrator and assistant), not the
 * catalogue management permission.
 */
final readonly class GetTreatmentsController
{
    public function __construct(
        private GetAgendaTreatmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $treatments = $this->useCase->execute();

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
