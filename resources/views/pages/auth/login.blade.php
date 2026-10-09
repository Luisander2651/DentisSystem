@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="grid w-full max-w-6xl overflow-hidden rounded-card border border-line bg-surface shadow-xl shadow-primary/10 md:grid-cols-2">
        <form id="login-form" class="w-full flex flex-col justify-center gap-6 p-6 sm:p-8 lg:p-10">
            @csrf

            @if ($errors->any())
                <div role="alert" class="rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full border border-secondary bg-primary-soft px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19.5A4.5 4.5 0 0 1 8.5 15h7a4.5 4.5 0 0 1 4.5 4.5" />
                        <circle cx="12" cy="8" r="3.2" />
                    </svg>
                    <span>Acceso al panel</span>
                </div>
                <x-ui.h1 class="text-left">Iniciar sesión</x-ui.h1>
                <p class="max-w-md text-sm leading-6 text-muted">Ingresa tus credenciales para continuar en el panel administrativo o de paciente.</p>
            </div>
            <div class="flex flex-col justify-between h-auto gap-y-4">
                <x-ui.input
                name="email"
                label="Correo"
                variant="email"
                placeholder="usuario@correo.com"
                autocomplete="email"
                required
                />
                <x-ui.input
                name="password"
                label="Contraseña"
                variant="password"
                autocomplete="current-password"
                required
                />
                <div class="flex flex-wrap items-center justify-between gap-x-4 text-sm">
                    <a href="{{ route('register') }}" class="inline-flex min-h-control items-center font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">
                        ¿No tienes cuenta? Regístrate
                    </a>
                    <a href="{{ route('password.request') }}" class="inline-flex min-h-control items-center font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                <div class="w-full">
                    <x-ui.button id="login-submit" variant="primary" type="submit" class="w-full">
                        Iniciar sesión
                    </x-ui.button>
                </div>
            </div>
        </form>

        <div class="relative hidden overflow-hidden bg-primary-soft p-10 text-ink md:order-first md:flex md:flex-col md:justify-between">
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
                    <span>Acceso a Dentissa</span>
                </div>
                <div class="space-y-4">
                    <h2 class="text-4xl font-black tracking-tight">Bienvenido de vuelta</h2>
                    <p class="max-w-md text-base leading-7 text-ink">Accede al panel con una experiencia visual más limpia, moderna y alineada al resto de la clínica.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-secondary bg-surface p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Acceso</p>
                        <p class="mt-2 text-sm text-ink">Ingreso rápido y seguro</p>
                    </div>
                    <div class="rounded-box border border-secondary bg-surface p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Soporte</p>
                        <p class="mt-2 text-sm text-ink">Flujo claro y sin fricción</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-8 overflow-hidden rounded-card border border-secondary bg-surface p-2 shadow-lg shadow-primary/10">
                <img src="{{ asset('images/brand/access.jpg') }}" alt="" class="h-80 w-full rounded-box object-cover" />
            </div>
        </div>
    </div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        const form = document.getElementById('login-form');
        const submitButton = document.getElementById('login-submit');

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);

            if (parts.length === 2) {
                return decodeURIComponent(parts.pop().split(';').shift());
            }

            return null;
        }

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const emailInput = form.querySelector('input[name="email"]');
            const passwordInput = form.querySelector('input[name="password"]');

            const email = emailInput ? emailInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            window.uiStatus.clearAnnouncements();

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                // Sanctum SPA flow: first obtain XSRF-TOKEN cookie.
                const csrfResponse = await fetch('/sanctum/csrf-cookie', {
                    method: 'GET',
                    credentials: 'include',
                });

                if (!csrfResponse.ok) {
                    throw new Error('csrf');
                }

                const xsrfToken = getCookie('XSRF-TOKEN');

                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': xsrfToken || '',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ email: email, password: password }),
                });

                const payload = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok) {
                    if (response.status === 401 || response.status === 422) {
                        window.uiStatus.announceError('El correo o la contraseña no son correctos.');
                    } else {
                        window.uiStatus.announceFailure(response.status, payload);
                    }

                    return;
                }

                window.location.href = '{{ url('/dashboard') }}';
            } catch (error) {
                window.uiStatus.announceFailure(0);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    })();
</script>
@endsection