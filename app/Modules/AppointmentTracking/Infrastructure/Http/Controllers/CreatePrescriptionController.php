<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\AppointmentTracking\Application\DTOs\CreatePrescriptionDTO;
use App\Modules\AppointmentTracking\Application\UseCases\CreatePrescriptionUseCase;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\PrescriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class CreatePrescriptionController
{
    public function __construct(
        private CreatePrescriptionUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = CreatePrescriptionDTO::create(
                appointmentTrackingId: (string) $request->route('appointmentTrackingId'),
                medication: $request->string('medication')->value(),
                dosage: $request->string('dosage')->value(),
                durationDays: $request->integer('duration_days'),
                dailyFrequency: $request->integer('daily_frequency'),
                instructions: $request->has('instructions') ? $request->string('instructions')->value() : null,
            );

            $prescription = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Prescription created successfully',
                'data' => new PrescriptionResource($prescription),
            ], 201);
        } catch (AppointmentTrackingException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
