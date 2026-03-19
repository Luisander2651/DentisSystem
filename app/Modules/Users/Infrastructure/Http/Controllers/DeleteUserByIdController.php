<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\UseCases\DeleteUserByIdUseCase;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;

use Illuminate\Http\JsonResponse;

final class DeleteUserByIdController
{
    public function __construct(
        private DeleteUserByIdUseCase $deleteUserByIdUseCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $id = request()->route('id');

        try {
            $this->deleteUserByIdUseCase->execute($id);
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UserException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (UserAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}