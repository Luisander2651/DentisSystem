<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.landing.inicio');
})->name('inicio');

Route::get('/contacto', function () {
    return view('pages.landing.contacto');
})->name('contacto');

Route::get('/galeria', function () {
    return view('pages.landing.galeria');
})->name('galeria');

Route::get('/acerca-de-nosotros', function () {
    return view('pages.landing.acerca');
})->name('acerca');

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('pages.auth.forgot-password');
})->name('password.request');

Route::get('/reset-password', function () {
    return view('pages.auth.reset-password');
})->name('password.reset');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/logout', function () {
        return view('pages.auth.logout');
    })->name('logout.page');
    
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('/expedientes-clinicos', function () {
        $user = request()->user();
        $roleName = strtolower((string) optional($user?->role)->name);

        if (!in_array($roleName, ['administrador', 'asistente'], true)) {
            abort(403, 'No autorizado.');
        }

        return view('pages.records.index');
    })->name('records.index');

    Route::get('/expedientes-clinicos/{patientId}', function (string $patientId) {
        $user = request()->user();
        $roleName = strtolower((string) optional($user?->role)->name);

        if (!in_array($roleName, ['administrador', 'asistente'], true)) {
            abort(403, 'No autorizado.');
        }

        return view('pages.records.index', [
            'selectedPatientId' => $patientId,
        ]);
    })->name('records.show');
});

Route::middleware(['auth:sanctum', 'only.admin'])->group(function () {
    Route::get('/usuarios', function () {
        return view('pages.usuarios.index');
    })->name('usuarios.index');

    Route::get('/contenido', function () {
        return view('pages.contenido.index');
    })->name('contenido.index');

    Route::get('/tratamientos', function () {
        return view('pages.tratamientos.index');
    })->name('tratamientos.index');

    Route::get('/pacientes', function () {
        return view('pages.patients.index');
    })->name('patients.index');

    Route::get('/agenda', function () {
        return view('pages.agenda.index');
    })->name('agenda.index');
    });