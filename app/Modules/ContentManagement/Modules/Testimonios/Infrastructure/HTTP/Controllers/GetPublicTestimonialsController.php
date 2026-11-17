<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\GetTestimonialsDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\GetTestimonialsUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Resources\TestimonialResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spec 027 (CA11, RS4.a for testimonials): the public list of testimonials, only the visible ones.
 * The status of the query string is never read, so an opinion just sent or one the clinic hid never
 * leaves through here. Same shape as the staff list, which keeps the hidden ones.
 */
final readonly class GetPublicTestimonialsController
{
    public function __construct(
        private GetTestimonialsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute(new GetTestimonialsDTO(id: is_string($request->query('id')) ? $request->query('id') : null, status: 'visible'));

            if (count($result) === 1) {
                return response()->json(new TestimonialResource($result[0]));
            }

            return response()->json(TestimonialResource::collection($result));
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
