<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\DeleteCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\DeleteCertificationUseCase;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class DeleteCertificationController
{
    public function __construct(
        private DeleteCertificationUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $dto = new DeleteCertificationDTO(id: $id);
            $this->useCase->execute($dto);
            return response()->json(['message' => 'Certification deleted successfully'], 200);
        } catch (CertificationException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
