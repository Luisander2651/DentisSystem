<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\GetCertificationsDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\GetCertificationsUseCase;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Resources\CertificationResource;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class GetCertificationsController
{
    public function __construct(
        private GetCertificationsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new GetCertificationsDTO(
                id: $request->query('id'),
                name: $request->query('name'),
            );

            $result = $this->useCase->execute($dto);

            if (count($result) === 1) {
                return response()->json(new CertificationResource($result[0]));
            }

            return response()->json(CertificationResource::collection($result));
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
