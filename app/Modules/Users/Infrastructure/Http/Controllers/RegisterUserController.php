<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\UseCases\SaveUserUseCase;
use App\Modules\Users\Aplication\DTOs\SaveUserDTO;

use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class RegisterUserController
{
    public function __construct(
        private SaveUserUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1. Recibimos los datos y creamos el DTO
            // Nota: Aquí podrías usar un FormRequest de Laravel para validar tipos
            $dto = SaveUserDTO::create(
                firstName: $request->string('first_name')->value(),
                lastName:  $request->string('last_name')->value(),
                email:     $request->string('email')->value(),
                password:  $request->string('password')->value(),
                roleId:    $request->string('role_id')->value(),
            );

            // 2. Ejecutamos el Caso de Uso
            $this->useCase->execute($dto);

            // 3. Respuesta de éxito
            return response()->json([
                'message' => 'User registered successfully'
            ], 201);

        } catch (UserException $e) {
            // Capturamos errores de negocio (ej: email duplicado)
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            // Capturamos errores de validación de Value Objects (ej: nombre corto)
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }catch (\Exception $e) {
            // Errores inesperados
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}