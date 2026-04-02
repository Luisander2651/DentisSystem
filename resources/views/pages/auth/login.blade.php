@extends('layouts.app')

@section('title', 'Iniciar Sesion')

@section('content')
<div class="w-full h-screen flex items-center justify-center bg-[#FDF1F6]">
    <div class="flex w-full max-w-3xl rounded-lg border border-[#F5C2D6] bg-white shadow-xl">
        <img src="{{ asset('storage/login.jpg') }}" alt="Description" class="w-1/2 rounded-lg">

        <form id="login-form" class="w-full flex flex-col p-6 gap-6">
            @csrf

            @if ($errors->any())
                <div class="rounded-md border border-[#F5C2D6] bg-[#FFF1F6] px-3 py-2 text-sm text-[#9D174D]">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="login-error" class="hidden rounded-md border border-[#F5C2D6] bg-[#FFF1F6] px-3 py-2 text-sm text-[#9D174D]"></div>

            <div>
                <x-ui.h1 class="text-center">Iniciar Sesion</x-ui.h1>
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
                <a href="{{ route('register') }}" class="text-end text-[#E91E63] hover:underline text-sm">
                    ¿No tienes cuenta? Regístrate
                </a>
                <div class="w-full">
                    <x-ui.button id="login-submit" variant="primary" type="submit" class="w-full sm:w-full cursor-pointer">
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