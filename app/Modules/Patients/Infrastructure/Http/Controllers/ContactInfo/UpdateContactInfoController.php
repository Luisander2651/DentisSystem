<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers\ContactInfo;

use App\Modules\Patients\Aplication\DTOs\ContactInfo\UpdateContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Aplication\UseCases\ContactInfo\UpdateContactInfoUseCase;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateContactInfoController
{
    public function __construct(
        private UpdateContactInfoUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = UpdateContactInfoDTO::create(
                phoneNumber: $request->string('phone_number')->value(),
                contactEmail: $request->string('contact_email')->value(),
                emergencyContact: $request->string('emergency_contact')->value(),
            );

            $patientId = (string) $request->route('patientId');

            $this->useCase->execute($patientId, $dto);

            return response()->json([
                'message' => 'Contact info updated successfully',
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
