<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetTreatmentsDTO;
use App\Modules\Appointments\Aplication\UseCases\GetTreatmentsUseCase;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use App\Modules\Appointments\Infrastructure\Http\Resources\TreatmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetTreatmentsAdminController
{
    public function __construct(
        private GetTreatmentsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $treatments = $this->useCase->execute(
                GetTreatmentsDTO::create(
                    id: $request->query('id'),
                    name: $request->query('name'),
                )
            );

            return response()->json([
                'data' => TreatmentResource::collection($treatments),
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}