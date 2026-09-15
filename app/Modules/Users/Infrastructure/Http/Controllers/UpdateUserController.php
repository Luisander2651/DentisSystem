<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\DTOs\UpdateUserDto;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Aplication\UseCases\UpdateUserUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Infrastructure\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class UpdateUserController
{
    public function __construct(
        private UpdateUserUseCase $useCase
    ) {}

    public function __invoke(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id, UpdateUserDto::create(
                firstName: $request->input('first_name'),
                lastName: $request->input('last_name'),
                roleId: $request->input('role_id'),
                status: $request->input('status'),
                newPassword: $request->input('new_password'),
            ));

            return response()->json(['message' => 'User updated successfully'], 200);
        } catch (UserException $e) {
            // BR-18: a missing user is 404, not the 409 this used to answer. 409 is kept
            // for the genuine conflict, which is a duplicate email.
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            // BR-19: UuidIdentifier throws a plain InvalidArgumentException, which does
            // NOT descend from ValueObjectsException, so a malformed id used to fall
            // through to the generic handler and answer 500. App\Core is left untouched:
            // it is shared with other modules, so the translation belongs here.
            return response()->json(['error' => 'The provided user id is not valid.'], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // BR-13 (SECURITY-15).
            Log::error('UpdateUserController: unexpected error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
