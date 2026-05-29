<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\SaveCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationInputException;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\SaveCertificationUseCase;
use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final readonly class SaveCertificationController
{
    public function __construct(
        private SaveCertificationUseCase $useCase,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $imageUrl = $request->file('image');

            $dto = new SaveCertificationDTO(
                name: $request->input('name'),
                description: $request->input('description'),
                date: $request->input('date'),
                image: $imageUrl,
            );

            $this->useCase->execute($dto);

            return response()->json(['message' => 'Certification saved successfully', 'data' => $dto], 201);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (CertificationInputException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (CertificationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (StorageException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}