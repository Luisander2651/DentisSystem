<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth.register');
})->name('register');


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/logout', function () {
        return view('pages.auth.logout');
    })->name('logout.page');
    
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth:sanctum', 'only.admin'])->group(function () {
    Route::get('/usuarios', function () {
        return view('pages.usuarios.index');
    })->name('usuarios.index');

    Route::get('/pacientes', function () {
        return view('pages.patients.index');
    })->name('patients.index');
});