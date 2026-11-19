<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\Requests\ListPageRequest;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentsByIdPatientDto;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\ListPatientAppointmentsPageUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use App\Modules\Appointments\Infrastructure\Http\Resources\AppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 028 (CA2): the history of appointments of the clinical record, ten per page with its `meta`.
 */
final class GetAppointmentsByPatientIdController
{
    public function __construct(
        private ListPatientAppointmentsPageUseCase $useCase,
    ) {}

    public function __invoke(ListPageRequest $request): JsonResponse
    {
        try {
            $dto = GetAppointmentsByIdPatientDto::create(
                patientId: (string) $request->route('patientId'),
            );

            $page = $this->useCase->execute($dto, $request->pageRequest(ListPatientAppointmentsPageUseCase::PAGE_SIZE));

            return response()->json([
                'message' => 'Appointments retrieved successfully',
                'data' => AppointmentResource::collection($page->items),
                'meta' => PageMeta::from($page),
            ], 200);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
