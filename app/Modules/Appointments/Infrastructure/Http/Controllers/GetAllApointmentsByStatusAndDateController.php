<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Appointments\Aplication\DTOs\GetAllAppointmentsByStatusAndDateDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\GetAllApointmentsByStatusAndDateUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use App\Modules\Appointments\Infrastructure\Http\Resources\AppointmentResource;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetAllApointmentsByStatusAndDateController
{
    public function __construct(
        private GetAllApointmentsByStatusAndDateUseCase $getAllApointmentsByStatusAndDateUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $getAllAppointmentsByStatusAndDateDTO = GetAllAppointmentsByStatusAndDateDTO::create(
                status: $request->query('status'),
                date: $request->query('date'),
            );

            $appointments = $this->getAllApointmentsByStatusAndDateUseCase->execute($getAllAppointmentsByStatusAndDateDTO);

            return response()->json([
                'message' => 'Appointments retrieved successfully',
                'data' => AppointmentResource::collection($appointments),
            ], 200);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}