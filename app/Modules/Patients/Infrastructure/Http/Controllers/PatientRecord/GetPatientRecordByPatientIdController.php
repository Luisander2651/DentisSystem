<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\PatientRecord;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Patients\Aplication\DTOs\PatientRecord\GetPatientRecordByPatientIdDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientRecord\PatientRecordAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\PatientRecord\GetPatientRecordByPatientIdUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetPatientRecordByPatientIdController
{
    public function __construct(
        private GetPatientRecordByPatientIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = GetPatientRecordByPatientIdDTO::create(
                patientId: (string) $request->route('patientId'),
            );

            $patientRecord = $this->useCase->execute($dto);

            return response()->json([
                'data' => new PatientRecordResource($patientRecord),
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (PatientRecordAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() === 404 ? 404 : 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
