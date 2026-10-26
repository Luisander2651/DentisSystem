<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\GetUnclosedAppointmentsUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Infrastructure\Http\Resources\DashboardAppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 020 (CA4, A13, TM8): a refusal leaves through the render of AuthorizationException, with
 * its usual body; any other failure answers the generic 500.
 */
final readonly class GetUnclosedAppointmentsController
{
    public function __construct(
        private GetUnclosedAppointmentsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $page = $this->useCase->execute();
            $today = now()->toImmutable();

            return response()->json(['data' => [
                'items' => array_map(fn (AppointmentEntity $appointment): DashboardAppointmentResource => DashboardAppointmentResource::ofEarlierDay($appointment, $today), $page->items),
                'total' => $page->total,
            ]], 200);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
