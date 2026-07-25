@extends('layouts.app')

@section('title', 'Restablecer Contraseña')

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
                    <span>Nueva credencial</span>
                </div>
                <div class="space-y-4">
                    <h2 class="text-4xl font-black tracking-tight">Renueva tu acceso</h2>
                    <p class="max-w-md text-base leading-7 text-white/80">Crea una contraseña nueva con una experiencia visual más elegante y consistente con la plataforma.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Privacidad</p>
                        <p class="mt-2 text-sm text-white/90">Cambio seguro y guiado</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Acceso</p>
                        <p class="mt-2 text-sm text-white/90">Listo para volver al panel</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-8 overflow-hidden rounded-[1.75rem] border border-white/15 bg-white/10 p-2 shadow-2xl">
                <img src="{{ asset('storage/login.jpg') }}" alt="Restablecer Contraseña" class="h-[20rem] w-full rounded-[1.25rem] object-cover opacity-90" />
            </div>
        </div>

        <div class="w-full flex flex-col justify-center p-6 gap-6 sm:p-8 lg:p-10">
            <div id="reset-error" class="hidden rounded-2xl border border-[#F5C2D6] bg-[#FFF7FA] px-4 py-3 text-sm text-[#9D174D] shadow-sm"></div>
            
            <div id="reset-success" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                ¡Contraseña restablecida con éxito! Ya puedes iniciar sesión con tus nuevas credenciales.
            </div>

            <!-- Formulario principal -->
            <form id="reset-form" class="flex flex-col gap-6">
                @csrf
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#B5114A]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                        </svg>
                        <span>Restablecimiento</span>
                    </div>
                    <x-ui.h1 class="text-left text-3xl! sm:text-4xl!">Nueva Contraseña</x-ui.h1>
                    <p class="max-w-md text-sm leading-6 text-slate-500">Escribe tu nueva contraseña para continuar con el acceso al sistema.</p>
                </div>

                <div class="flex flex-col gap-y-4">
                    <x-ui.input
                        name="password"
                        label="Nueva Contraseña"
                        variant="password"
                        placeholder="********"
                        required
                    />

                    <x-ui.input
                        name="confirm_password"
                        label="Confirmar Nueva Contraseña"
                        variant="password"
                        placeholder="********"
                        required
                    />

                    <div class="w-full">
                        <x-ui.button id="reset-submit" variant="primary" type="submit" class="w-full cursor-pointer shadow-lg shadow-[#B5114A]/20">
                            Actualizar Contraseña
                        </x-ui.button>
                    </div>
                </div>
            </form>

            <div class="border-t border-slate-100 pt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-[#B5114A] hover:underline">
                    Volver al Inicio de Sesión
                </a>
            </div>
        </div>
    </div>
</div>

@vite('resources/js/pages/auth/reset-password.js')
@endsection
