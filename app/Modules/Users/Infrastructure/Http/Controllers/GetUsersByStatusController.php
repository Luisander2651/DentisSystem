<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Aplication\UseCases\GetUsersByStatusUseCase;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetUsersByStatusController
{
    public function __construct(
        private GetUsersByStatusUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            
            $users = $this->useCase->execute($status);

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
        }catch (\Exception $e) {
            // Errores inesperados
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}