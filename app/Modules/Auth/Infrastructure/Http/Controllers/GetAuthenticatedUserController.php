<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Auth\Aplication\UseCases\GetAuthenticatedUserUseCase;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/auth/me: the attendance microservice checks that the app token still belongs to
 * an active administrator. An expired or revoked token never gets here (sanctum answers 401).
 */
final class GetAuthenticatedUserController
{
    public function __construct(
        private GetAuthenticatedUserUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $user = $this->useCase->execute();

            return response()->json([
                'data' => new UserResource($user),
            ], 200)->header('Cache-Control', 'no-store');
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
