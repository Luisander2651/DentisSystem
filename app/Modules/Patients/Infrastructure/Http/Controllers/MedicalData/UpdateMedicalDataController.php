<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\UpdateMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\MedicalData\UpdateMedicalDataUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateMedicalDataController
{
    public function __construct(
        private UpdateMedicalDataUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdateMedicalDataDTO::create(
                bloodType: $request->string('blood_type')->value(),
                allergies: $request->input('allergies'),
                medications: $request->input('medications'),
                lastDentistVisit: $request->input('last_dentist_visit'),
            );

            $patientId = (string) $request->route('patientId');

            $this->useCase->execute($patientId, $dto);

            return response()->json([
                'message' => 'Medical data updated successfully',
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
