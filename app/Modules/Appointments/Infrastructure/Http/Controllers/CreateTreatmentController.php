<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\CreateTreatmentDTO;
use App\Modules\Appointments\Aplication\UseCases\CreateTreatmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CreateTreatmentController
{
    public function __construct(
        private CreateTreatmentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->useCase->execute(
                CreateTreatmentDTO::create(
                    name: (string) $request->string('name'),
                    description: (string) $request->string('description'),
                    time: (string) $request->string('time'),
                )
            );

            return response()->json([
                'message' => 'Treatment created successfully',
            ], 201);
        } catch (TreatmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}