<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\DeletePatientByIdUseCase;
use App\Modules\Patients\Domain\Exceptions\PatientException;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeletePatientByIdController
{
    public function __construct(
        private DeletePatientByIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $id = (string) $request->route('id');

            $this->useCase->execute($id);

            return response()->json([
                'message' => 'Patient deleted successfully',
            ], 200);
        } catch (PatientException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PatientAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}