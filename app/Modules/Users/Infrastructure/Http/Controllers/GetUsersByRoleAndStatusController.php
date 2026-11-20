<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Users\Aplication\DTOs\ListUsersPageDTO;
use App\Modules\Users\Aplication\UseCases\ListUsersPageUseCase;
use App\Modules\Users\Infrastructure\Http\Requests\GetUsersRequest;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 028 (CA1, CA5): one page of the staff with its `meta` and the counts by role of the whole
 * list.
 */
final class GetUsersByRoleAndStatusController
{
    public function __construct(
        private ListUsersPageUseCase $useCase
    ) {}

    public function __invoke(GetUsersRequest $request): JsonResponse
    {
        try {
            $pageRequest = $request->pageRequest(ListUsersPageDTO::DEFAULT_PER_PAGE);

            $result = $this->useCase->execute(ListUsersPageDTO::create(
                page: $pageRequest->page,
                perPage: $pageRequest->size,
                status: $request->validated('status'),
                role: $request->validated('role'),
            ));

            // BR-8: UserResource projects six fields and the password hash is not one of
            // them, so the credential never reaches the wire.
            return response()->json([
                'data' => UserResource::collection($result['page']->items),
                'meta' => PageMeta::from($result['page'], $result['counts']),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
