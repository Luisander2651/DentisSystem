@extends('layouts.app')

@section('title', 'Registrarse')

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
					<span>Crear cuenta Dentissa</span>
				</div>
				<div class="space-y-4">
					<h2 class="text-4xl font-black tracking-tight">Únete al ecosistema</h2>
					<p class="max-w-md text-base leading-7 text-ink">Diseña tu acceso con una interfaz más clara, más cuidada y consistente con la experiencia visual del sitio.</p>
				</div>

				<div class="grid gap-3 sm:grid-cols-2">
					<div class="rounded-box border border-secondary bg-surface p-4">
						<p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Rápido</p>
						<p class="mt-2 text-sm text-ink">Registro simple y directo</p>
					</div>
					<div class="rounded-box border border-secondary bg-surface p-4">
						<p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Seguro</p>
						<p class="mt-2 text-sm text-ink">Acceso protegido y claro</p>
					</div>
				</div>
			</div>

			<div class="relative z-10 mt-8 overflow-hidden rounded-card border border-secondary bg-surface p-2 shadow-lg shadow-primary/10">
				<img src="{{ asset('images/brand/access.jpg') }}" alt="" class="h-80 w-full rounded-box object-cover" />
			</div>
		</div>

		<form id="register-form" class="w-full flex flex-col justify-center p-6 gap-4 sm:p-8 lg:p-10">
			@csrf

			<div class="space-y-3">
				<div class="inline-flex items-center gap-2 rounded-full border border-secondary bg-primary-soft px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink">
					<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M4 19.5A4.5 4.5 0 0 1 8.5 15h7a4.5 4.5 0 0 1 4.5 4.5" />
						<circle cx="12" cy="8" r="3.2" />
					</svg>
					<span>Registro de usuario</span>
				</div>
				<x-ui.h1 class="text-left">Crear cuenta</x-ui.h1>
				<p class="max-w-md text-sm leading-6 text-muted">Completa tus datos para entrar al sistema con una experiencia más pulida y alineada al diseño actual.</p>
			</div>

			<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
				<x-ui.input
					name="first_name"
					label="Nombre"
					placeholder="Juan"
					autocomplete="given-name"
					required
				/>

				<x-ui.input
					name="last_name"
					label="Apellido"
					placeholder="Pérez"
					autocomplete="family-name"
					required
				/>
			</div>

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
				autocomplete="new-password"
				required
			/>

			<x-ui.input
				name="confirm_password"
				label="Confirmar contraseña"
				variant="password"
				autocomplete="new-password"
				required
			/>

			<a href="{{ route('login') }}" class="inline-flex min-h-control items-center justify-end text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">
				¿Ya tienes cuenta? Inicia sesión
			</a>

			<div class="w-full">
				<x-ui.button id="register-submit" variant="primary" type="submit" class="w-full sm:w-auto">
					Registrarme
				</x-ui.button>
			</div>
		</form>
	</div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
	(function () {
		const form = document.getElementById('register-form');
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
			// BR-23: recortar bordes igual que el resto de campos, para que lo que el
			// usuario ve sea exactamente lo que se guarda. Los espacios internos se respetan.
			const password = passwordInput ? passwordInput.value.trim() : '';
			const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

			window.uiStatus.clearAnnouncements();

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
					window.uiStatus.announceFailure(response.status, payload);

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
