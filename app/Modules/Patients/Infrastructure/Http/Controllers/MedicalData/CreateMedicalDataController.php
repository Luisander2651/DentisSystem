<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\CreateMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\MedicalData\SaveMedicalDataUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreateMedicalDataController
{
    public function __construct(
        private SaveMedicalDataUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $patientId = (string) $request->route('patientId');

            $dto = CreateMedicalDataDTO::create(
                patientId: $patientId,
                bloodType: $request->string('blood_type')->value(),
                allergies: $request->input('allergies'),
                medications: $request->input('medications'),
                lastDentistVisit: $request->input('last_dentist_visit'),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Medical data created successfully',
            ], 201);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (MedicalDataAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
