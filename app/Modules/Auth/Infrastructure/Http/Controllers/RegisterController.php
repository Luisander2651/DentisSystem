<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\LoginDTO;
use App\Modules\Auth\Aplication\DTOs\RegisterDTO;
use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LoginUseCase;
use App\Modules\Auth\Aplication\UseCases\RegisterUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegisterController
{
    public function __construct(
        private RegisterUseCase $registerUseCase,
        private LoginUseCase $loginUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::create(
                firstName: $request->string('first_name')->value(),
                lastName: $request->string('last_name')->value(),
                email: $request->string('email')->value(),
                password: $request->string('password')->value(),
                confirmPassword: $request->string('confirm_password')->value(),
            );

            $this->registerUseCase->execute($dto);

            $result = $this->loginUseCase->execute(
                LoginDTO::create(
                    email: $dto->email,
                    password: $dto->password,
                )
            );

            $cookie = cookie(
                name: 'auth_token',
                value: $result['token'],
                minutes: (int) config('sanctum.expiration', 1440),
                path: (string) config('session.path', '/'),
                domain: config('session.domain'),
                secure: (bool) config('session.secure', false),
                httpOnly: true,
                raw: false,
                sameSite: (string) config('session.same_site', 'lax'),
            );

            unset($result['token']);

            return response()->json([
                'message' => 'Patient registered successfully',
                'data' => $result,
            ], 201)->withCookie($cookie);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
