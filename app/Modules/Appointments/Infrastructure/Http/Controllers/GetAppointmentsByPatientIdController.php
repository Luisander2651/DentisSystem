<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Aplication\DTOs\GetAppointmentsByIdPatientDto;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\GetAppointentByPatientIdUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use App\Modules\Appointments\Infrastructure\Http\Resources\AppointmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetAppointmentsByPatientIdController
{
    public function __construct(
        private GetAppointentByPatientIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = GetAppointmentsByIdPatientDto::create(
                patientId: (string) $request->route('patientId'),
            );

            $appointments = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Appointments retrieved successfully',
                'data' => AppointmentResource::collection($appointments),
            ], 200);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
