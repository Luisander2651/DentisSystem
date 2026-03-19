<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Infrastructure\Http\Controllers\LoginController;
use App\Modules\Auth\Infrastructure\Http\Controllers\RegisterController;
use App\Modules\Auth\Infrastructure\Http\Controllers\LogoutController;
use App\Modules\Users\Infrastructure\Http\Controllers\RegisterUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\UpdateUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\GetUsersByRoleAndStatusController;
use App\Modules\Users\Infrastructure\Http\Controllers\DeleteUserByIdController;
use App\Core\Middlewares\OnlyAdmin;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginController::class);
        Route::post('/register', RegisterController::class);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', LogoutController::class);
        });
    });
        
    Route::prefix('users')->middleware(['auth:sanctum', OnlyAdmin::class])->group(function (): void {
        Route::post('/', RegisterUserController::class);
        Route::put('/{id}', UpdateUserController::class);
        Route::get('/', GetUsersByRoleAndStatusController::class);
        Route::delete('/{id}', DeleteUserByIdController::class);
    });
});