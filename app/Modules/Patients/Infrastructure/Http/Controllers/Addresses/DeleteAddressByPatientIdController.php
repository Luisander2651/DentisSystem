<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\Addresses;

use App\Modules\Patients\Aplication\DTOs\Addresses\DeleteAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\Addresses\DeleteAddressByPatientIdUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteAddressByPatientIdController
{
    public function __construct(
        private DeleteAddressByPatientIdUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = DeleteAddressDTO::create(
                patientId: (string) $request->route('patientId'),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Address deleted successfully',
            ], 200);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AddressAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
