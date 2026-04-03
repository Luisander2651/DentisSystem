<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Domain\Exceptions\PatientException;
use App\Modules\Patients\Aplication\UseCases\GetPatientByIdUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetPatientByIdController
{
    public function __construct(
        private GetPatientByIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $patients = $this->useCase->execute((string) $request->route('id'));

            return response()->json([
                'data' => new PatientResource($patients),
            ], 200);
        } catch (PatientException | ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PatientAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}