<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Patients\Aplication\DTOs\CreatePatientDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\SavePatientUseCase;
use App\Modules\Patients\Domain\Exceptions\PatientException;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreatePatientController
{
    public function __construct(
        private SavePatientUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = CreatePatientDTO::create(
                firstName: $request->string('first_name')->value(),
                lastName: $request->string('last_name')->value(),
                email: $request->string('email')->value(),
                password: $request->string('password')->value(),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Patient created successfully',
            ], 201);
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