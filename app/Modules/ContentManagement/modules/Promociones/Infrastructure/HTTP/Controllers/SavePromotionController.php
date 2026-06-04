<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\SavePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\SavePromotionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SavePromotionController
{
    public function __construct(
        private SavePromotionUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new SavePromotionDTO(
                name: (string) $request->input('name'),
                description: (string) $request->input('description'),
                discountPercentage: $request->filled('discount_percentage') ? (float) $request->input('discount_percentage') : null,
                startDate: (string) $request->input('start_date'),
                endDate: (string) $request->input('end_date'),
                status: $request->input('status'),
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Promotion created successfully'], 201);
        } catch (PromotionInputException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}