<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\UpdateTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\UpdateTestimonialUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions\TestimonialException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class UpdateTestimonialController
{
    public function __construct(
        private UpdateTestimonialUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new UpdateTestimonialDTO(
                id: $id,
                author: $request->input('author'),
                description: $request->input('description'),
                status: $request->input('status'),
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Testimonial updated successfully'], 200);
        } catch (TestimonialException | TestimonialInputException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}