<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use App\Modules\Users\Aplication\UseCases\GetUsersByRoleAndStatusUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Aplication\DTOs\GetUsersByStatusAndRoleDTO;
use App\Core\Authorization\Exceptions\AuthorizationException;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class GetUsersByRoleAndStatusController
{
    public function __construct(
        private GetUsersByRoleAndStatusUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = GetUsersByStatusAndRoleDTO::create(
                status: $request->query('status'),
                role: $request->query('role')
            );

            $users = $this->useCase->execute($dto);

            return response()->json([
                'data' => UserResource::collection($users)
            ], 200);
        } catch (UserException $e) {
            // Capturamos errores de negocio (ej: email duplicado)
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            // Capturamos errores de validación de Value Objects (ej: nombre corto)
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }catch (\Exception $e) {
            // Errores inesperados
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}