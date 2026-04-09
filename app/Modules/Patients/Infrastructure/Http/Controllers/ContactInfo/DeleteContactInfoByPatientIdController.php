<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\ContactInfo;

use App\Modules\Patients\Aplication\DTOs\ContactInfo\DeleteContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\ContactInfo\DeleteContactInfoByPatientIdUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteContactInfoByPatientIdController
{
    public function __construct(
        private DeleteContactInfoByPatientIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = DeleteContactInfoDTO::create(
                patientId: (string) $request->route('patientId'),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Contact info deleted successfully',
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ContactInfoAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
