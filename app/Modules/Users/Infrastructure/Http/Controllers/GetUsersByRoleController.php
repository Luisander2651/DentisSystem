<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use App\Modules\Users\Aplication\UseCases\GetUsersByRoleUseCase;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class GetUsersByRoleController
{
    public function __construct(
        private GetUsersByRoleUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
        $role = $request->query('role');

        $users = $this->useCase->execute($role);

        return response()->json([
            'data' => UserResource::collection($users)
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
    }
    }
}