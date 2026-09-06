<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\GetGalleryImagesDTO;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\GetGalleryImagesUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Resources\GalleryImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetGalleryImagesController
{
    public function __construct(
        private GetGalleryImagesUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new GetGalleryImagesDTO(
                id: $request->query('id'),
                url: $request->query('url'),
                status: $request->query('status'),
            );

            $result = $this->useCase->execute($dto);

            if (count($result) === 1) {
                return response()->json(new GalleryImageResource($result[0]));
            }

            return response()->json(GalleryImageResource::collection($result));
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}