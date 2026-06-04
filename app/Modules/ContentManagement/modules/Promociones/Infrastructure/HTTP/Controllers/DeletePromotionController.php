<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\DeletePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\DeletePromotionUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DeletePromotionController
{
    public function __construct(
        private DeletePromotionUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new DeletePromotionDTO(id: $id);

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Promotion deleted successfully'], 200);
        } catch (PromotionException | PromotionInputException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}