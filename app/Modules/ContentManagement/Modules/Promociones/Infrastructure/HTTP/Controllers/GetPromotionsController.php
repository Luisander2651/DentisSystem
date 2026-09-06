<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\GetPromotionsDTO;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\GetPromotionsUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Resources\PromotionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetPromotionsController
{
    public function __construct(
        private GetPromotionsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new GetPromotionsDTO(
                id: $request->query('id'),
                name: $request->query('name'),
                status: $request->query('status'),
            );

            $result = $this->useCase->execute($dto);

            if (count($result) === 1) {
                return response()->json(new PromotionResource($result[0]));
            }

            return response()->json(PromotionResource::collection($result));
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}