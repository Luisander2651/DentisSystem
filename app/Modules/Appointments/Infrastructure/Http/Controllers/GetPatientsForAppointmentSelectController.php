<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\AuthorizeAgendaSelectorsUseCase;
use App\Modules\Patients\Aplication\DTOs\GetPatientsByStatusDTO;
use App\Modules\Patients\Aplication\UseCases\GetPatientsByStatusUseCase;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientResource;
use Illuminate\Http\JsonResponse;

final readonly class GetPatientsForAppointmentSelectController
{
    public function __construct(
        private AuthorizeAgendaSelectorsUseCase $authorizeSelectors,
        private GetPatientsByStatusUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $this->authorizeSelectors->execute();

            $patients = $this->useCase->execute(GetPatientsByStatusDTO::create());

            return response()->json([
                'data' => PatientResource::collection($patients),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
