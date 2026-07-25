@extends('layouts.landing')

@section('title', 'Contacto y Ubicación - Dentissa')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
        <!-- Contact details & Social media cards -->
        <div class="space-y-10">
            <!-- Intro Text -->
            <div class="space-y-4">
                <h1 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Contacto</h1>
                <p class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">¡Ponte en contacto con nosotros!</p>
                <p class="text-lg leading-7 text-slate-600">
                    Estamos aquí para resolver tus dudas y ayudarte a agendar tu próxima consulta. Elige el medio que prefieras y te atenderemos con gusto.
                </p>
            </div>

            <!-- Social Media Cards -->
            <div class="grid gap-6 sm:grid-cols-3">
                <!-- WhatsApp Card -->
                <a href="https://wa.me/521234567890?text=Hola,%20me%20gustaria%20agendar%20una%20cita" target="_blank" rel="noopener noreferrer" class="group rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-xs hover:shadow-md hover:border-[#B5114A]/30 transition-all duration-300 flex flex-col justify-between min-h-[180px]">
                    <div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-transform duration-200 group-hover:scale-105">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 4a8 8 0 0 0-12.7 9.6L6 19l5.4-1.3A8 8 0 1 0 20 4z" />
                                <path d="M9 9c.6 1.7 2.3 3.3 4 4" />
                            </svg>
                        </div>
                        <h2 class="mt-4 font-bold text-slate-900 text-base">WhatsApp</h2>
                        <p class="mt-1 text-xs text-slate-400">Respuesta inmediata</p>
                    </div>
                    <span class="mt-4 text-xs font-semibold text-[#B5114A] inline-flex items-center gap-1 group-hover:translate-x-1 transition-transform duration-200">
                        Escríbenos &rarr;
                    </span>
                </a>

                <!-- Instagram Card -->
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="group rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-xs hover:shadow-md hover:border-[#B5114A]/30 transition-all duration-300 flex flex-col justify-between min-h-[180px]">
                    <div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-transform duration-200 group-hover:scale-105">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5" />
                                <circle cx="12" cy="12" r="4" />
                                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                            </svg>
                        </div>
                        <h2 class="mt-4 font-bold text-slate-900 text-base">Instagram</h2>
                        <p class="mt-1 text-xs text-slate-400">@dentissa_clinic</p>
                    </div>
                    <span class="mt-4 text-xs font-semibold text-[#B5114A] inline-flex items-center gap-1 group-hover:translate-x-1 transition-transform duration-200">
                        Síguenos &rarr;
                    </span>
                </a>

                <!-- Facebook Card -->
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="group rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-xs hover:shadow-md hover:border-[#B5114A]/30 transition-all duration-300 flex flex-col justify-between min-h-[180px]">
                    <div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-transform duration-200 group-hover:scale-105">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z" />
                            </svg>
                        </div>
                        <h2 class="mt-4 font-bold text-slate-900 text-base">Facebook</h2>
                        <p class="mt-1 text-xs text-slate-400">Dentissa Oficial</p>
                    </div>
                    <span class="mt-4 text-xs font-semibold text-[#B5114A] inline-flex items-center gap-1 group-hover:translate-x-1 transition-transform duration-200">
                        Visítanos &rarr;
                    </span>
                </a>
            </div>

            <!-- Direct Contact List -->
            <div class="rounded-3xl border border-[#F5C2D6]/40 bg-[#FFF7FA] p-8 space-y-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 border-b border-[#F5C2D6]/20 pb-3">Información de la Clínica</h3>
                <ul class="space-y-4 text-sm text-slate-600">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11z" />
                                <circle cx="12" cy="10" r="2.2" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-bold text-slate-800">Dirección</p>
                            <p class="mt-1">Av. Universidad 1200, Colonia Centro, Ciudad de México, CP 03100</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.2 19.2 0 0 1-6-6A19.8 19.8 0 0 1 2.1 3.2 2 2 0 0 1 4.1 1h2a2 2 0 0 1 2 1.7c.1.9.3 1.7.5 2.5a2 2 0 0 1-.5 2.1L7.2 8.9a16 16 0 0 0 6 6l1.6-1c.6-.4 1.4-.5 2.1-.2.8.3 1.6.5 2.5.5a2 2 0 0 1 1.6 1.7z" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-bold text-slate-800">Teléfono</p>
                            <p class="mt-1">+52 55 1234 5678</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="M3 7l9 6 9-6" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-bold text-slate-800">Correo Electrónico</p>
                            <p class="mt-1">contacto@dentissa.com</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Google Map Section -->
        <div class="relative min-h-[400px] h-full">
            <div class="absolute inset-0 rounded-3xl border border-[#F5C2D6]/40 bg-white p-3 shadow-md">
                <iframe class="h-full w-full rounded-2xl grayscale hover:grayscale-0 transition-all duration-500" 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3762.6617260592963!2d-99.1722352850934!3d19.367568986922432!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d1ff38bb6a27e7%3A0xe96bf0ff4ea50eb0!2sAv.%20Universidad%201200%2C%20Xoco%2C%20Benito%20Ju%C3%A1rez%2C%2003330%20Ciudad%20de%20M%C3%A9xico%2C%20CDMX!5e0!3m2!1ses-419!2smx!4v1688647000000!5m2!1ses-419!2smx" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</div>
@endsection
