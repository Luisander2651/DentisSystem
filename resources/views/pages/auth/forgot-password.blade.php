@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="grid w-full max-w-6xl overflow-hidden rounded-card border border-line bg-surface shadow-xl shadow-primary/10 md:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-primary-soft p-10 text-ink md:flex md:flex-col md:justify-between">
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -left-12 top-10 h-48 w-48 rounded-full bg-surface/60 blur-3xl"></div>
                <div class="absolute -right-8 bottom-8 h-56 w-56 rounded-full bg-secondary/40 blur-3xl"></div>
            </div>

            <div class="relative z-10 space-y-6">
                <x-ui.brand variant="logo" class="w-56" />
                <div class="inline-flex items-center gap-2 rounded-full border border-secondary bg-surface px-4 py-2 text-sm font-semibold text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                    </svg>
                    <span>Recuperación segura</span>
                </div>
                <div class="space-y-4">
                    <h2 class="text-4xl font-black tracking-tight">Vuelve a entrar sin fricción</h2>
                    <p class="max-w-md text-base leading-7 text-ink">Solicita el enlace de acceso con una interfaz más refinada y coherente con el resto del sistema.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-secondary bg-surface p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Enlace</p>
                        <p class="mt-2 text-sm text-ink">Solicitud guiada y clara</p>
                    </div>
                    <div class="rounded-box border border-secondary bg-surface p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Tiempo</p>
                        <p class="mt-2 text-sm text-ink">Proceso simple y rápido</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-8 overflow-hidden rounded-card border border-secondary bg-surface p-2 shadow-lg shadow-primary/10">
                <img src="{{ asset('images/brand/access.jpg') }}" alt="" class="h-80 w-full rounded-box object-cover" />
            </div>
        </div>

        <form id="forgot-form" class="w-full flex flex-col justify-center p-6 gap-6 sm:p-8 lg:p-10">
            @csrf

            <div id="forgot-error" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger"></div>
            
            <div id="forgot-success" class="hidden rounded-box border border-success bg-success-soft px-4 py-3 text-sm font-semibold text-success">
                Listo. Si el correo está registrado, te enviamos el enlace para restablecer tu contraseña.
            </div>

            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full border border-secondary bg-primary-soft px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                    </svg>
                    <span>Restablecimiento</span>
                </div>
                <x-ui.h1 class="text-left">Recuperar contraseña</x-ui.h1>
                <p class="max-w-md text-sm leading-6 text-muted">Ingresa tu correo y te enviaremos el enlace para volver a acceder.</p>
            </div>

            <div class="flex flex-col justify-between h-auto gap-y-4">
                <x-ui.input
                    name="email"
                    label="Correo electrónico"
                    variant="email"
                    placeholder="usuario@correo.com"
                    autocomplete="email"
                    required
                />

                <div class="w-full">
                    <x-ui.button id="forgot-submit" variant="primary" type="submit" class="w-full sm:w-auto">
                        Enviar enlace
                    </x-ui.button>
                </div>
            </div>

            <div class="border-t border-line pt-4 text-center">
                <a href="{{ route('login') }}" class="inline-flex min-h-control items-center text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">
                    Volver al inicio de sesión
                </a>
            </div>
        </form>
    </div>
</div>

@vite('resources/js/pages/auth/forgot-password.js')
@endsection
