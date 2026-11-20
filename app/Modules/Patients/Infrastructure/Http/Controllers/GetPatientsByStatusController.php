<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Patients\Aplication\DTOs\ListPatientsPageDTO;
use App\Modules\Patients\Aplication\UseCases\ListPatientsPageUseCase;
use App\Modules\Patients\Infrastructure\Http\Requests\GetPatientsRequest;
use App\Modules\Patients\Infrastructure\Http\Resources\PatientResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 028 (CA1, CA4, CA5): one page of the patients with its `meta` and the counts of the whole
 * list.
 */
final class GetPatientsByStatusController
{
    public function __construct(
        private ListPatientsPageUseCase $useCase,
    ) {}

    public function __invoke(GetPatientsRequest $request): JsonResponse
    {
        try {
            $pageRequest = $request->pageRequest(ListPatientsPageDTO::DEFAULT_PER_PAGE);

            $result = $this->useCase->execute(ListPatientsPageDTO::create(
                page: $pageRequest->page,
                perPage: $pageRequest->size,
                status: $request->status(),
                search: $request->searchText(),
            ));

            return response()->json([
                'data' => PatientResource::collection($result['page']->items),
                'meta' => PageMeta::from($result['page'], $result['counts']),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
