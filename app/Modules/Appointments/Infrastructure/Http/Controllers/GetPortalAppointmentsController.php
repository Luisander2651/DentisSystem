<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\Requests\ListPageRequest;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\ListPortalAppointmentsPageUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\PortalAppointmentResource;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/portal/appointments (spec 025, CA3, CA4, CA13; spec 028, CA2): one page of the
 * appointments of the patient of the session, ten by default, with its `meta`. It reads only the
 * page and its size from the request: an id sent by the client is ignored.
 */
final class GetPortalAppointmentsController
{
    public function __construct(
        private ListPortalAppointmentsPageUseCase $useCase,
    ) {}

    public function __invoke(ListPageRequest $request): JsonResponse
    {
        try {
            $page = $this->useCase->execute($request->pageRequest(ListPortalAppointmentsPageUseCase::PAGE_SIZE));

            return response()->json([
                'data' => PortalAppointmentResource::collection($page->items),
                'meta' => PageMeta::from($page),
            ]);
        } catch (AuthorizationException $e) {
            // The 403 of the whole API, rendered in bootstrap/app.php.
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
