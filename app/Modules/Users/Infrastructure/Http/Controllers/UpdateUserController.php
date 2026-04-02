<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\UseCases\UpdateUserUseCase;
use App\Modules\Users\Aplication\DTOs\UpdateUserDto;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class UpdateUserController
{
    public function __construct(
        private UpdateUserUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1. Recibimos los datos y creamos el DTO
            // Nota: Aquí podrías usar un FormRequest de Laravel para validar tipos
            $dto = UpdateUserDto::create(
                firstName: $request->string('first_name')->value(),
                lastName:  $request->string('last_name')->value(),
                roleId:    $request->string('role_id')->value(),
                status:    $request->string('status')->value(),
                newPassword: $request->string('new_password')->value(),
            );

            $userId = $request->route('id');

            // 2. Ejecutamos el Caso de Uso
            $this->useCase->execute($userId, $dto);

            // 3. Respuesta de éxito
            return response()->json([
                'message' => 'User updated successfully'
            ], 200);

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