<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Users\Infrastructure\Http\Controllers\RegisterUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\UpdateUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\GetUsersByRoleAndStatusController;
use App\Modules\Users\Infrastructure\Http\Controllers\DeleteUserByIdController;
    
Route::post('/users', RegisterUserController::class);
Route::put('/users/{id}', UpdateUserController::class);
Route::get('/users', GetUsersByRoleAndStatusController::class);
Route::delete('/users/{id}', DeleteUserByIdController::class);