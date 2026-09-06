<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\AppointmentTracking\Application\DTOs\UpdatePrescriptionDTO;
use App\Modules\AppointmentTracking\Application\Exceptions\PrescriptionApplicationException;
use App\Modules\AppointmentTracking\Application\UseCases\UpdatePrescriptionUseCase;
use App\Modules\AppointmentTracking\Domain\Exceptions\PrescriptionException;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\PrescriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class UpdatePrescriptionController
{
    public function __construct(
        private UpdatePrescriptionUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdatePrescriptionDTO::create(
                prescriptionId: (string) $request->route('id'),
                medication: $request->has('medication') ? $request->string('medication')->value() : null,
                dosage: $request->has('dosage') ? $request->string('dosage')->value() : null,
                durationDays: $request->has('duration_days') ? $request->integer('duration_days') : null,
                dailyFrequency: $request->has('daily_frequency') ? $request->integer('daily_frequency') : null,
                instructions: $request->has('instructions') ? $request->string('instructions')->value() : null,
            );

            $prescription = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Prescription updated successfully',
                'data' => new PrescriptionResource($prescription),
            ], 200);
        } catch (PrescriptionException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (PrescriptionApplicationException $e) {
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
