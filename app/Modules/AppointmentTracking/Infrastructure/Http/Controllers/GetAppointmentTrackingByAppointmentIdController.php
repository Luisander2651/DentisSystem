<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\AppointmentTracking\Application\DTOs\GetAppointmentTrackingByAppointmentIdDTO;
use App\Modules\AppointmentTracking\Application\UseCases\GetAppointmentTrackingByAppointmentIdUseCase;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\AppointmentTrackingRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class GetAppointmentTrackingByAppointmentIdController
{
    public function __construct(
        private GetAppointmentTrackingByAppointmentIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $record = $this->useCase->execute(
                GetAppointmentTrackingByAppointmentIdDTO::create(
                    appointmentId: (string) $request->route('id'),
                )
            );

            if (! $record) {
                return response()->json(['error' => 'Appointment tracking not found.'], 404);
            }

            return response()->json([
                'data' => new AppointmentTrackingRecordResource($record),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
