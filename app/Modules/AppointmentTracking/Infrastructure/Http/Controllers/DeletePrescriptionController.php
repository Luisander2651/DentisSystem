<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\AppointmentTracking\Application\DTOs\DeletePrescriptionDTO;
use App\Modules\AppointmentTracking\Application\UseCases\DeletePrescriptionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class DeletePrescriptionController
{
    public function __construct(
        private DeletePrescriptionUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->useCase->execute(
                DeletePrescriptionDTO::create(
                    prescriptionId: (string) $request->route('id'),
                )
            );

            return response()->json([
                'message' => 'Prescription deleted successfully',
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
