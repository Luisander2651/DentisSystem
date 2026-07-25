@extends('layouts.app')

@section('title', 'Iniciar Sesion')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-[#F5C2D6] bg-white/90 shadow-[0_30px_100px_-40px_rgba(181,17,74,0.35)] backdrop-blur md:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-[#B5114A] via-[#D61B5B] to-[#7D0D33] p-10 text-white md:flex md:flex-col md:justify-between">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute -left-12 top-10 h-48 w-48 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute -right-8 bottom-8 h-56 w-56 rounded-full bg-[#F5C2D6]/30 blur-3xl"></div>
            </div>

            <div class="relative z-10 space-y-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white/90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                    </svg>
                    <span>Dentissa Premium Access</span>
                </div>
                <div class="space-y-4">
                    <h2 class="text-4xl font-black tracking-tight">Bienvenido de vuelta</h2>
                    <p class="max-w-md text-base leading-7 text-white/80">Accede al panel con una experiencia visual más limpia, moderna y alineada al resto de la clínica.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Acceso</p>
                        <p class="mt-2 text-sm text-white/90">Ingreso rápido y seguro</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Soporte</p>
                        <p class="mt-2 text-sm text-white/90">Flujo claro y sin fricción</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-8 overflow-hidden rounded-[1.75rem] border border-white/15 bg-white/10 p-2 shadow-2xl">
                <img src="{{ asset('storage/login.jpg') }}" alt="Dentissa" class="h-[20rem] w-full rounded-[1.25rem] object-cover opacity-90" />
            </div>
        </div>

        <form id="login-form" class="w-full flex flex-col justify-center gap-6 p-6 sm:p-8 lg:p-10">
            @csrf

            @if ($errors->any())
                <div class="rounded-2xl border border-[#F5C2D6] bg-[#FFF7FA] px-4 py-3 text-sm text-[#9D174D] shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="login-error" class="hidden rounded-2xl border border-[#F5C2D6] bg-[#FFF7FA] px-4 py-3 text-sm text-[#9D174D] shadow-sm"></div>

            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#B5114A]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19.5A4.5 4.5 0 0 1 8.5 15h7a4.5 4.5 0 0 1 4.5 4.5" />
                        <circle cx="12" cy="8" r="3.2" />
                    </svg>
                    <span>Acceso al panel</span>
                </div>
                <x-ui.h1 class="text-left text-3xl! sm:text-4xl!">Iniciar Sesion</x-ui.h1>
                <p class="max-w-md text-sm leading-6 text-slate-500">Ingresa tus credenciales para continuar en el panel administrativo o de paciente.</p>
            </div>
            <div class="flex flex-col justify-between h-auto gap-y-4">
                <x-ui.input
                name="email"
                label="Correo"
                variant="email"
                placeholder="usuario@correo.com"
                class=""
                />
                <x-ui.input
                name="password"
                label="Password"
                variant="password"
                placeholder="********"
                class=""
                />
                <div class="flex justify-between items-center gap-4 text-sm flex-wrap">
                    <a href="{{ route('register') }}" class="font-semibold text-[#B5114A] hover:underline">
                        ¿No tienes cuenta? Regístrate
                    </a>
                    <a href="{{ route('password.request') }}" class="font-semibold text-[#B5114A] hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                <div class="w-full">
                    <x-ui.button id="login-submit" variant="primary" type="submit" class="w-full sm:w-full cursor-pointer shadow-lg shadow-[#B5114A]/20">
                        Iniciar Sesion
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('login-form');
        const errorBox = document.getElementById('login-error');
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

            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
            }

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
                    throw new Error('No se pudo inicializar la cookie CSRF.');
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
                    const message = payload.error || payload.message || 'No se pudo iniciar sesion.';

                    if (errorBox) {
                        errorBox.textContent = message;
                        errorBox.classList.remove('hidden');
                    }

                    return;
                }

                window.location.href = '{{ url('/dashboard') }}';
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = 'Error de conexion. Intentalo de nuevo.';
                    errorBox.classList.remove('hidden');
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    })();
</script>
@endsection