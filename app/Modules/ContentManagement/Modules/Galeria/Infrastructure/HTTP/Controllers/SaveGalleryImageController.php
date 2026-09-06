<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\SaveGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\SaveGalleryImageUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class SaveGalleryImageController
{
    public function __construct(
        private SaveGalleryImageUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $image = $request->file('image');

            if ($image !== null && ! $image instanceof UploadedFile) {
                return response()->json(['error' => 'Invalid image uploaded'], 400);
            }

            $dto = new SaveGalleryImageDTO(
                description: (string) $request->input('description'),
                image: $image,
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Gallery image created successfully'], 201);
        } catch (GalleryImageInputException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (StorageException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}