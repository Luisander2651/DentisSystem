<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Users\Infrastructure\Http\Controllers\RegisterUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\UpdateUserController;

Route::post('/users/register', RegisterUserController::class);
Route::post('/users/update/{id}', UpdateUserController::class);