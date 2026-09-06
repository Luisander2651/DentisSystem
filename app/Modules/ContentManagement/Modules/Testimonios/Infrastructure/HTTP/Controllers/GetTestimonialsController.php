<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\GetTestimonialsDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\GetTestimonialsUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Resources\TestimonialResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetTestimonialsController
{
    public function __construct(
        private GetTestimonialsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new GetTestimonialsDTO(
                id: $request->query('id'),
                status: $request->query('status'),
            );

            $result = $this->useCase->execute($dto);

            if (count($result) === 1) {
                return response()->json(new TestimonialResource($result[0]));
            }

            return response()->json(TestimonialResource::collection($result));
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}