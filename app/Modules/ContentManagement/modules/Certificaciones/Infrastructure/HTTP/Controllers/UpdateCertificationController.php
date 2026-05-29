<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\UpdateCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\UpdateCertificationUseCase;
use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationInputException;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final readonly class UpdateCertificationController
{
    public function __construct(
        private UpdateCertificationUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $image = $request->file('image');

            if ($image !== null && ! $image instanceof UploadedFile) {
                return response()->json(['error' => 'Invalid image uploaded'], 400);
            }

            $dto = new UpdateCertificationDTO(
                id: $id,
                name: $request->input('name'),
                description: $request->input('description'),
                image: $image,
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Certification updated successfully'], 200);
        } catch (CertificationException | CertificationInputException $e) {
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
