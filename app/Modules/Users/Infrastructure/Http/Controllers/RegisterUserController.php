<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\DTOs\SaveUserDTO;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Aplication\UseCases\SaveUserUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Infrastructure\Http\Requests\RegisterUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class RegisterUserController
{
    public function __construct(
        private SaveUserUseCase $useCase
    ) {}

    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        /** @var array{first_name:string,last_name:string,email:string,password:string,role_id:string} $data */
        $data = $request->validated();

        try {
            $this->useCase->execute(SaveUserDTO::create(
                firstName: $data['first_name'],
                lastName: $data['last_name'],
                email: $data['email'],
                password: $data['password'],
                roleId: $data['role_id'],
            ));

            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (UserException $e) {
            // Business conflict: the email already belongs to another staff member (BR-2).
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            // BR-13 (SECURITY-15): the internal message stays in the log, never in the
            // response.
            Log::error('RegisterUserController: unexpected error', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
