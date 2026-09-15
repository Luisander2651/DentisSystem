<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Aplication\UseCases\DeleteUserByIdUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class DeleteUserByIdController
{
    public function __construct(
        private DeleteUserByIdUseCase $deleteUserByIdUseCase,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $this->deleteUserByIdUseCase->execute($id);

            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (UserException $e) {
            // BR-18: a missing user is 404.
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            // BR-19: see UpdateUserController for why this is translated here and not in
            // App\Core\Domain\UuidIdentifier.
            return response()->json(['error' => 'The provided user id is not valid.'], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // BR-13 (SECURITY-15).
            Log::error('DeleteUserByIdController: unexpected error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
