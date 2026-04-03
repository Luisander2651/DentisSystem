<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Modules\Patients\Aplication\DTOs\UpdatePatientDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\UpdatePatientUseCase;
use App\Modules\Patients\Domain\Exceptions\PatientException;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdatePatientController
{
    public function __construct(
        private UpdatePatientUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdatePatientDTO::create(
                firstName: $request->string('first_name')->value(),
                lastName: $request->string('last_name')->value(),
                status: $request->string('status')->value(),
                newPassword: $request->string('new_password')->value(),
            );

            $id = (string) $request->route('id');

            $this->useCase->execute($id, $dto);

            return response()->json([
                'message' => 'Patient updated successfully',
            ], 200);
        } catch (PatientException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PatientAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}