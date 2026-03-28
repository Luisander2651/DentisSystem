<?php

use App\Modules\Auth\Infrastructure\Http\Controllers\WebLoginController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth.register');
})->name('register');

