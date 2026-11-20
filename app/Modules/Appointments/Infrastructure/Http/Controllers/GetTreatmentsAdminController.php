<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\ListTreatmentsPageDTO;
use App\Modules\Appointments\Aplication\UseCases\ListTreatmentsPageUseCase;
use App\Modules\Appointments\Infrastructure\Http\Requests\GetTreatmentsAdminRequest;
use App\Modules\Appointments\Infrastructure\Http\Resources\TreatmentResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 028 (CA1, CA4, CA5): one page of the treatments with its `meta` and the stats of the whole
 * catalogue.
 */
final readonly class GetTreatmentsAdminController
{
    public function __construct(
        private ListTreatmentsPageUseCase $useCase,
    ) {}

    public function __invoke(GetTreatmentsAdminRequest $request): JsonResponse
    {
        try {
            $pageRequest = $request->pageRequest(ListTreatmentsPageDTO::DEFAULT_PER_PAGE);

            $result = $this->useCase->execute(ListTreatmentsPageDTO::create(
                page: $pageRequest->page,
                perPage: $pageRequest->size,
                search: $request->searchText(),
            ));

            return response()->json([
                'data' => TreatmentResource::collection($result['page']->items),
                'meta' => PageMeta::from($result['page'], $result['counts']),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
