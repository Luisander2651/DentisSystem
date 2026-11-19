<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\PageMeta;
use App\Core\Http\Requests\ListPageRequest;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\ListTestimonialsPageUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Resources\TestimonialResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 028 (CA1, CA5; D9): the testimonials of the panel, one page of twelve with its `meta`. The public
 * site keeps its own controller.
 */
final readonly class ListTestimonialsPageController
{
    public const DEFAULT_PER_PAGE = 12;

    public function __construct(
        private ListTestimonialsPageUseCase $useCase,
    ) {}

    public function __invoke(ListPageRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute($request->pageRequest(self::DEFAULT_PER_PAGE));

            return response()->json([
                'data' => TestimonialResource::collection($result['page']->items),
                'meta' => PageMeta::from($result['page'], $result['counts']),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
