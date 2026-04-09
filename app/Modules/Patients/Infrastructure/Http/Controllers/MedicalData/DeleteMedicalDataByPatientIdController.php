<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\DeleteMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\MedicalData\DeleteMedicalDataByPatientIdUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteMedicalDataByPatientIdController
{
    public function __construct(
        private DeleteMedicalDataByPatientIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = DeleteMedicalDataDTO::create(
                patientId: (string) $request->route('patientId'),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Medical data deleted successfully',
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (MedicalDataAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
