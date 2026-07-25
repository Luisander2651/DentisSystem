@extends('layouts.app')

@section('title', 'Registrarse')

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
					<span>Crear cuenta Dentissa</span>
				</div>
				<div class="space-y-4">
					<h2 class="text-4xl font-black tracking-tight">Únete al ecosistema</h2>
					<p class="max-w-md text-base leading-7 text-white/80">Diseña tu acceso con una interfaz más clara, más cuidada y consistente con la experiencia visual del sitio.</p>
				</div>

				<div class="grid gap-3 sm:grid-cols-2">
					<div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
						<p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Rápido</p>
						<p class="mt-2 text-sm text-white/90">Registro simple y directo</p>
					</div>
					<div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
						<p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Seguro</p>
						<p class="mt-2 text-sm text-white/90">Acceso protegido y claro</p>
					</div>
				</div>
			</div>

			<div class="relative z-10 mt-8 overflow-hidden rounded-[1.75rem] border border-white/15 bg-white/10 p-2 shadow-2xl">
				<img src="{{ asset('storage/login.jpg') }}" alt="Registro" class="h-[20rem] w-full rounded-[1.25rem] object-cover opacity-90" />
			</div>
		</div>

		<form id="register-form" class="w-full flex flex-col justify-center p-6 gap-4 sm:p-8 lg:p-10">
			@csrf

			<div id="register-error" class="hidden rounded-2xl border border-[#F5C2D6] bg-[#FFF7FA] px-4 py-3 text-sm text-[#9D174D] shadow-sm"></div>

			<div class="space-y-3">
				<div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#B5114A]">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M4 19.5A4.5 4.5 0 0 1 8.5 15h7a4.5 4.5 0 0 1 4.5 4.5" />
						<circle cx="12" cy="8" r="3.2" />
					</svg>
					<span>Registro de usuario</span>
				</div>
				<x-ui.h1 class="text-left text-3xl! sm:text-4xl!">Crear Cuenta</x-ui.h1>
				<p class="max-w-md text-sm leading-6 text-slate-500">Completa tus datos para entrar al sistema con una experiencia más pulida y alineada al diseño actual.</p>
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

			<a href="{{ route('login') }}" class="text-end text-sm font-semibold text-[#B5114A] hover:underline">
				¿Ya tienes cuenta? Inicia Sesion
			</a>

			<div class="w-full">
				<x-ui.button id="register-submit" variant="primary" type="submit" class="w-full cursor-pointer shadow-lg shadow-[#B5114A]/20">
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
