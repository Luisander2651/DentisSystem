<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Users\Infrastructure\Http\Controllers\RegisterUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\UpdateUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\GetUsersByRoleController;

Route::post('/users/register', RegisterUserController::class);
Route::post('/users/update/{id}', UpdateUserController::class);
Route::get('/users/role', GetUsersByRoleController::class);