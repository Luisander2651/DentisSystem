<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\UpdatePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\UpdatePromotionUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class UpdatePromotionController
{
    public function __construct(
        private UpdatePromotionUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new UpdatePromotionDTO(
                id: $id,
                name: $request->input('name'),
                description: $request->input('description'),
                discountPercentage: $request->filled('discount_percentage') ? (float) $request->input('discount_percentage') : null,
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                status: $request->input('status'),
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Promotion updated successfully'], 200);
        } catch (PromotionException | PromotionInputException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}