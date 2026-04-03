<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Modules\Patients\Aplication\DTOs\GetPatientsByStatusDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\GetPatientsByStatusUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetPatientsByStatusController
{
    public function __construct(
        private GetPatientsByStatusUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = GetPatientsByStatusDTO::create(
                status: $request->query('status'),
            );

            $patients = $this->useCase->execute($dto);

            return response()->json([
                'data' => PatientResource::collection($patients),
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PatientAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}