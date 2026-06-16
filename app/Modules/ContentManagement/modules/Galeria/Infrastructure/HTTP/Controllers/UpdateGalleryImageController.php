<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\UpdateGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\UpdateGalleryImageUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class UpdateGalleryImageController
{
    public function __construct(
        private UpdateGalleryImageUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $image = $request->file('image');

            if ($image !== null && ! $image instanceof UploadedFile) {
                return response()->json(['error' => 'Invalid image uploaded'], 400);
            }

            $dto = new UpdateGalleryImageDTO(
                id: $id,
                description: $request->input('description'),
                image: $image,
                status: $request->has('status') ? (string) $request->input('status') : null,
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Gallery image updated successfully'], 200);
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