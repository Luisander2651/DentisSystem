<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\DTOs\GetUsersByStatusAndRoleDTO;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Aplication\UseCases\GetUsersByRoleAndStatusUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Infrastructure\Http\Requests\GetUsersRequest;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class GetUsersByRoleAndStatusController
{
    public function __construct(
        private GetUsersByRoleAndStatusUseCase $useCase
    ) {}

    public function __invoke(GetUsersRequest $request): JsonResponse
    {
        try {
            $users = $this->useCase->execute(GetUsersByStatusAndRoleDTO::create(
                status: $request->query('status'),
                role: $request->query('role'),
            ));

            // BR-8: UserResource projects six fields and the password hash is not one of
            // them, so the credential never reaches the wire.
            return response()->json(['data' => UserResource::collection($users)], 200);
        } catch (UserException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            // BR-13 (SECURITY-15).
            Log::error('GetUsersByRoleAndStatusController: unexpected error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
