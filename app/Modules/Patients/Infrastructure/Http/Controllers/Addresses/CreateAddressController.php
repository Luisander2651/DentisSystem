<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\Addresses;

use App\Modules\Patients\Aplication\DTOs\Addresses\CreateAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\Addresses\SaveAddressUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreateAddressController
{
    public function __construct(
        private SaveAddressUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $patientId = (string) $request->route('patientId');

            $dto = CreateAddressDTO::create(
                patientId: $patientId,
                street: $request->string('street')->value(),
                city: $request->string('city')->value(),
                state: $request->string('state')->value(),
                postalCode: $request->string('postal_code')->value(),
            );

            $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Address created successfully',
            ], 201);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AddressAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
