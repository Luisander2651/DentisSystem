@extends('layouts.app')

@section('title', 'Registrarse')

@section('content')
<div class="w-full min-h-screen flex items-center justify-center bg-[#FDF1F6] py-8 px-4">
	<div class="flex w-full max-w-4xl rounded-lg border border-[#F5C2D6] bg-white shadow-xl overflow-hidden">
		<img src="{{ asset('storage/login.jpg') }}" alt="Registro" class="hidden md:block w-1/2 object-cover">

		<form id="register-form" class="w-full md:w-1/2 flex flex-col p-6 gap-4">
			@csrf

			<div id="register-error" class="hidden rounded-md border border-[#F5C2D6] bg-[#FFF1F6] px-3 py-2 text-sm text-[#9D174D]"></div>

			<div>
				<x-ui.h1 class="text-center">Crear Cuenta</x-ui.h1>
			</div>

			<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
				<x-ui.input
					name="first_name"
					label="Nombre"
					placeholder="Juan"
					class=""
				/>

				<x-ui.input
					name="last_name"
					label="Apellido"
					placeholder="Perez"
					class=""
				/>
			</div>

			<x-ui.input
				name="email"
				label="Correo"
				variant="email"
				placeholder="usuario@correo.com"
				class=""
			/>

			<x-ui.input
				name="password"
				label="Contraseña"
				variant="password"
				placeholder="********"
				class=""
			/>

			<x-ui.input
				name="confirm_password"
				label="Confirmar Contraseña"
				variant="password"
				placeholder="********"
				class=""
			/>

			<a href="{{ route('login') }}" class="text-end text-[#E91E63] hover:underline text-sm">
				¿Ya tienes cuenta? Inicia Sesion
			</a>

			<div class="w-full">
				<x-ui.button id="register-submit" variant="primary" type="submit" class="w-full cursor-pointer">
					Registrarme
				</x-ui.button>
			</div>
		</form>
	</div>
</div>

<script>
	(function () {
		const form = document.getElementById('register-form');
		const errorBox = document.getElementById('register-error');
		const submitButton = document.getElementById('register-submit');

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

			const firstNameInput = form.querySelector('input[name="first_name"]');
			const lastNameInput = form.querySelector('input[name="last_name"]');
			const emailInput = form.querySelector('input[name="email"]');
			const passwordInput = form.querySelector('input[name="password"]');
			const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');

			const firstName = firstNameInput ? firstNameInput.value.trim() : '';
			const lastName = lastNameInput ? lastNameInput.value.trim() : '';
			const email = emailInput ? emailInput.value.trim() : '';
			const password = passwordInput ? passwordInput.value : '';
			const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

			if (errorBox) {
				errorBox.classList.add('hidden');
				errorBox.textContent = '';
			}

			if (submitButton) {
				submitButton.disabled = true;
			}

			try {
				await fetch('{{ url('/sanctum/csrf-cookie') }}', {
					method: 'GET',
					credentials: 'include',
				});

				const csrfToken = getCookie('XSRF-TOKEN');

				const response = await fetch('{{ url('/api/v1/auth/register') }}', {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-XSRF-TOKEN': csrfToken || '',
					},
					credentials: 'include',
					body: JSON.stringify({
						first_name: firstName,
						last_name: lastName,
						email: email,
						password: password,
						confirm_password: confirmPassword,
					}),
				});

				const payload = await response.json().catch(function () {
					return {};
				});

				if (!response.ok) {
					const message = payload.error || payload.message || 'No se pudo completar el registro.';

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
