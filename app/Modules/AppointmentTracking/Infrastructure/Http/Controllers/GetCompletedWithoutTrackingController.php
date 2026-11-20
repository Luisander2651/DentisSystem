<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\Requests\PendingPageRequest;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Infrastructure\Http\Resources\DashboardAppointmentResource;
use App\Modules\AppointmentTracking\Application\UseCases\GetCompletedWithoutTrackingUseCase;
use Illuminate\Http\JsonResponse;

/**
 * Spec 020 (CA7, A13, TM6): the same fields as the other lists of the dashboard, with
 * `can.register`. A refusal leaves through the render of AuthorizationException, with its usual
 * body; any other failure answers the generic 500. Spec 028 (CA2): `data` keeps its shape
 * (`items`, `total`) and `meta` says the page.
 */
final readonly class GetCompletedWithoutTrackingController
{
    public function __construct(
        private GetCompletedWithoutTrackingUseCase $useCase,
    ) {}

    public function __invoke(PendingPageRequest $request): JsonResponse
    {
        try {
            $page = $this->useCase->execute($request->page());
            $today = now()->toImmutable();

            return response()->json(['data' => [
                'items' => array_map(fn (AppointmentEntity $appointment): array => [
                    ...DashboardAppointmentResource::ofEarlierDay($appointment, $today)->toArray($request),
                    'can' => ['register' => true],
                ], $page->items),
                'total' => $page->total,
            ], 'meta' => PageMeta::from($page)], 200);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
