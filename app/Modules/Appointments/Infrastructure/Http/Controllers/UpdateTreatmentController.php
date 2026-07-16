<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\UpdateTreatmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\TreatmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\UpdateTreatmentUseCase;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class UpdateTreatmentController
{
    public function __construct(
        private UpdateTreatmentUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdateTreatmentDTO::create(
                id: (string) $request->route('id'),
                name: $request->has('name') ? $request->string('name')->value() : null,
                description: $request->has('description') ? $request->string('description')->value() : null,
                time: $request->has('time') ? $request->string('time')->value() : null,
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Treatment updated successfully',
                'data' => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'description' => $dto->description,
                    'time' => $dto->time,
                ],
            ], 200);
        } catch (TreatmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException | TreatmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}