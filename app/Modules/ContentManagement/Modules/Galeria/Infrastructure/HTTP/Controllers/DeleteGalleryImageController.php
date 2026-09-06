<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\DeleteGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\DeleteGalleryImageUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DeleteGalleryImageController
{
    public function __construct(
        private DeleteGalleryImageUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new DeleteGalleryImageDTO(id: $id);

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Gallery image deleted successfully'], 200);
        } catch (GalleryImageException | GalleryImageInputException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (StorageException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}