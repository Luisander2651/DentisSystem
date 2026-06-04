<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SaveTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\SaveTestimonialUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SaveTestimonialController
{
    public function __construct(
        private SaveTestimonialUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new SaveTestimonialDTO(
                author: (string) $request->input('author'),
                description: (string) $request->input('description'),
                status: $request->input('status'),
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Testimonial created successfully'], 201);
        } catch (TestimonialInputException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}