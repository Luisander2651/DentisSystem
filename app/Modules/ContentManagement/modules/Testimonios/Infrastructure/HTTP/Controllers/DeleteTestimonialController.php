<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\DeleteTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\DeleteTestimonialUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions\TestimonialException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DeleteTestimonialController
{
    public function __construct(
        private DeleteTestimonialUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new DeleteTestimonialDTO(id: $id);

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Testimonial deleted successfully'], 200);
        } catch (TestimonialException | TestimonialInputException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}