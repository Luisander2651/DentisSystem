<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\DeleteTreatmentDTO;
use App\Modules\Appointments\Aplication\UseCases\DeleteTreatmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DeleteTreatmentController
{
    public function __construct(
        private DeleteTreatmentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->useCase->execute(
                DeleteTreatmentDTO::create(
                    (string) $request->route('id'),
                )
            );

            return response()->json([
                'message' => 'Treatment deleted successfully',
            ], 200);
        } catch (TreatmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}