@extends('layouts.landing')

@section('title', 'Dentissa - Clínica Dental Premium')

@section('content')
<div class="space-y-20 pb-20">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-[#FDF1F6]/30 py-24 sm:py-32">
        <div class="absolute inset-y-0 right-1/2 -z-10 -mr-96 w-[200%] origin-top-right skew-x-[-30deg] bg-white shadow-xs ring-1 ring-slate-100 sm:-mr-80 lg:-mr-96" aria-hidden="true"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
                <!-- Text Content -->
                <div class="max-w-2xl space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-sm font-semibold text-[#B5114A]">
                        <svg class="h-4 w-4 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                        </svg>
                        <span>Sonrisas sanas y naturales</span>
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl font-sans">
                        La mejor tecnología para tu <span class="text-[#B5114A]">salud dental</span>
                    </h1>
                    <p class="text-lg leading-8 text-slate-600">
                        En Dentissa nos apasiona crear sonrisas hermosas y saludables. Ofrecemos tratamientos dentales integrales de alta calidad en un ambiente cálido, seguro y diseñado para tu comodidad.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <a href="https://wa.me/521234567890?text=Hola,%20me%20gustaria%20agendar%20una%20citas" target="_blank" rel="noopener noreferrer">
                            <x-ui.button variant="primary" class="w-full sm:w-auto px-8 py-3 text-base shadow-lg shadow-pink-500/20">
                                Agenda una Cita
                            </x-ui.button>
                        </a>
                        <a href="{{ route('contacto') }}">
                            <x-ui.button variant="principal" class="w-full sm:w-auto px-8 py-3 text-base">
                                Ver Ubicación
                            </x-ui.button>
                        </a>
                    </div>
                </div>

                <!-- Hero Image with floating stats -->
                <div class="relative mx-auto max-w-md lg:max-w-none lg:mx-0">
                    <div class="relative overflow-hidden rounded-3xl border border-[#F5C2D6]/30 bg-white p-2 shadow-xl">
                        <!-- We use a premium CSS dentist scene with gradients instead of standard empty placeolders -->
                        <div class="h-80 w-full rounded-2xl bg-gradient-to-br from-[#FFFDF6] via-[#FDF1F6] to-[#B5114A]/10 flex items-center justify-center p-8 text-center relative overflow-hidden">
                            <!-- Background abstract circle -->
                            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#B5114A]/5"></div>
                            
                            <div class="space-y-4 z-10">
                                <svg class="mx-auto h-16 w-16 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M7 3c-1.7 0-3 1.4-3 3.1 0 1.2.6 2.4 1.2 3.5.7 1.2 1.4 2.8 1.4 5.7 0 3 1.4 6.7 2.8 6.7 1.3 0 1.8-1.9 2.6-4.5.4-1.3.8-2.7 2-2.7s1.6 1.4 2 2.7c.8 2.6 1.3 4.5 2.6 4.5 1.4 0 2.8-3.7 2.8-6.7 0-2.9.7-4.5 1.4-5.7.6-1.1 1.2-2.3 1.2-3.5C20 4.4 18.7 3 17 3c-1.3 0-2.1.5-2.9 1.2-.8.7-1.5 1.3-2.1 1.3s-1.3-.6-2.1-1.3C9.1 3.5 8.3 3 7 3z" />
                                </svg>
                                <h3 class="text-xl font-bold text-slate-800">Dentissa Premium Care</h3>
                                <p class="text-sm text-slate-500 max-w-xs mx-auto">Equipamiento moderno y especialistas certificados listos para cuidar de ti.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badges -->
                    <div class="absolute -left-6 top-8 rounded-2xl bg-white p-4 shadow-md border border-slate-50 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FDF1F6] text-[#B5114A] font-bold">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">100% Seguro</p>
                            <p class="text-[10px] text-slate-400">Normas COFEPRIS</p>
                        </div>
                    </div>

                    <div class="absolute -right-6 bottom-8 rounded-2xl bg-white p-4 shadow-md border border-slate-50 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FDF1F6] text-[#B5114A] font-bold">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">5.0 Estrellas</p>
                            <p class="text-[10px] text-slate-400">Opiniones de pacientes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
            <h2 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Nuestros Servicios</h2>
            <p class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Tratamientos odontológicos especializados</p>
            <p class="text-slate-500">Diseñamos soluciones personalizadas para mejorar tu estética dental y salud bucal.</p>
        </div>

        <!-- 3up / 2down format using responsive grid -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 justify-center">
            <!-- Service 1: Limpieza -->
            <div class="col-span-1 md:col-span-2 group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-[#F5C2D6]/40">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Limpieza y Prevención</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Limpieza profunda con ultrasonido para eliminar sarro, prevenir caries y mantener tus encías completamente sanas.
                </p>
            </div>

            <!-- Service 2: Ortodoncia -->
            <div class="col-span-1 md:col-span-2 group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-[#F5C2D6]/40">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M8 15c1.2-1 2.5-1.5 4-1.5s2.8.5 4 1.5" />
                        <path d="M9 10h.01" />
                        <path d="M15 10h.01" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Ortodoncia</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Alinea tus dientes con brackets tradicionales o alineadores invisibles. Ortodoncia de vanguardia para niños y adultos.
                </p>
            </div>

            <!-- Service 3: Implantes -->
            <div class="col-span-1 md:col-span-2 group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-[#F5C2D6]/40">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 7l10 10" />
                        <path d="M6 8l2-2 2 2-2 2z" />
                        <path d="M14 16l2-2 2 2-2 2z" />
                        <path d="M5 19l3-3" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Implantes Dentales</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Recupera la funcionalidad y estética de tu boca reemplazando piezas perdidas con implantes de titanio altamente duraderos.
                </p>
            </div>

            <!-- Service 4: Blanqueamiento -->
            <div class="col-span-1 md:col-span-3 lg:col-start-2 lg:col-span-2 group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-[#F5C2D6]/40">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 4h12l4 6-10 10L2 10z" />
                        <path d="M9 4l3 16 3-16" />
                        <path d="M2 10h20" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Blanqueamiento Dental</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Aclara el tono de tus dientes de forma segura y rápida con tecnología láser. Resultados visibles en una sola sesión.
                </p>
            </div>

            <!-- Service 5: Odontopediatria -->
            <div class="col-span-1 md:col-span-3 lg:col-span-2 group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-[#F5C2D6]/40">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="3" />
                        <path d="M7 20c1.2-3 3.1-5 5-5s3.8 2 5 5" />
                        <path d="M9 11l-1 2" />
                        <path d="M15 11l1 2" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Odontopediatría</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Atención dental especializada y amigable para los más pequeños de la casa, creando hábitos saludables desde la infancia.
                </p>
            </div>
        </div>
    </section>

    <!-- Promotions Section (Dynamic Backend load) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-baseline justify-between gap-4 border-b border-[#F5C2D6] pb-5 mb-10">
            <div>
                <h2 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Promociones Especiales</h2>
                <p class="text-3xl font-bold tracking-tight text-slate-900 mt-1">Aprovecha nuestras ofertas del mes</p>
            </div>
            <a href="https://wa.me/521234567890" target="_blank" class="text-sm font-semibold text-[#B5114A] hover:underline">Preguntar por otras promociones &rarr;</a>
        </div>

        <!-- Loading / Errors / Cards List -->
        <div data-promotions-loading class="py-12 text-center text-slate-400">
            <svg class="animate-spin mx-auto h-8 w-8 text-[#B5114A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm">Cargando promociones...</p>
        </div>

        <div data-promotions-error class="hidden rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600 text-center"></div>

        <div data-promotions-list class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"></div>

        <div data-promotions-empty class="hidden py-12 text-center bg-slate-50 rounded-3xl border border-slate-100">
            <svg class="mx-auto h-10 w-10 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 7H4v4h16V7z" />
                <path d="M6 11v8h12v-8" />
                <path d="M12 7v12" />
                <path d="M12 7c-1.7 0-3-1.1-3-2.5S10.3 2 12 7z" />
                <path d="M12 7c1.7 0 3-1.1 3-2.5S13.7 2 12 7z" />
            </svg>
            <h3 class="mt-4 text-sm font-semibold text-slate-900">Sin promociones por el momento</h3>
            <p class="mt-2 text-xs text-slate-500">Suscríbete o contáctanos para conocer sobre futuros descuentos.</p>
        </div>
    </section>

    <!-- Certifications Section (Dynamic Backend load) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-baseline justify-between gap-4 border-b border-[#F5C2D6] pb-5 mb-10">
            <div>
                <h2 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Certificaciones</h2>
                <p class="text-3xl font-bold tracking-tight text-slate-900 mt-1">Respaldos y sellos que fortalecen nuestra atención</p>
            </div>
            <p class="text-sm font-medium text-slate-500">Estándares, reconocimientos y validaciones institucionales.</p>
        </div>

        <div data-certifications-loading class="py-12 text-center text-slate-400">
            <svg class="animate-spin mx-auto h-8 w-8 text-[#B5114A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm">Cargando certificaciones...</p>
        </div>

        <div data-certifications-error class="hidden rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600 text-center"></div>

        <div data-certifications-list class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"></div>

        <div data-certifications-empty class="hidden py-12 text-center bg-white rounded-3xl border border-[#F5C2D6]">
            <svg class="mx-auto h-10 w-10 text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 15l-4.5 2.5 1-5L5 9.5l5.1-.5L12 4l1.9 5 5.1.5-3.5 3 1 5z" />
                <path d="M12 15v5" />
            </svg>
            <h3 class="mt-4 text-sm font-semibold text-slate-900">Sin certificaciones por el momento</h3>
            <p class="mt-2 text-xs text-slate-500">Muy pronto compartiremos los sellos que avalan nuestra práctica clínica.</p>
        </div>
    </section>

    <!-- Testimonials Section (Dynamic Backend load) -->
    <section class="bg-[#FFF7FA] py-20 text-slate-900 rounded-3xl mx-4 sm:mx-6 lg:mx-8 shadow-xl shadow-[#F5C2D6]/20 border border-[#F5C2D6]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <h2 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Testimonios</h2>
                <p class="text-3xl font-bold tracking-tight sm:text-4xl text-slate-900">Lo que dicen nuestros pacientes</p>
                <p class="text-slate-600">Nuestra prioridad es tu comodidad y satisfacción. Estas son algunas de sus experiencias.</p>
            </div>

            <!-- Loading / Errors / Cards List -->
            <div data-testimonials-loading class="py-12 text-center text-slate-500">
                <svg class="animate-spin mx-auto h-8 w-8 text-[#B5114A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-sm">Cargando comentarios...</p>
            </div>

            <div data-testimonials-error class="hidden rounded-2xl border border-red-100 bg-white p-4 text-sm text-red-600 text-center shadow-sm"></div>

            <div data-testimonials-list class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"></div>

            <div data-testimonials-empty class="hidden py-12 text-center text-slate-500">
                <svg class="mx-auto h-10 w-10 text-[#B5114A]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" />
                </svg>
                <p class="mt-4 text-sm">Próximamente estaremos compartiendo las opiniones de nuestros pacientes.</p>
            </div>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-4 mb-12">
            <h2 class="text-base font-semibold uppercase tracking-wider text-[#B5114A]">Preguntas Frecuentes</h2>
            <p class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Resolveremos tus dudas</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ 1 -->
            <div class="faq-item rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button type="button" class="faq-trigger flex w-full items-center justify-between px-6 py-5 text-left font-semibold text-slate-900 hover:text-[#B5114A] transition-colors focus:outline-none">
                    <span>¿Cada cuánto tiempo debo ir al dentista para una limpieza?</span>
                    <span class="faq-icon text-slate-400 shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
                        Se recomienda realizar una limpieza dental profesional cada 6 meses. Esto ayuda a prevenir la acumulación de sarro, diagnosticar a tiempo posibles caries y mantener las encías en óptimo estado.
                    </p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="faq-item rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button type="button" class="faq-trigger flex w-full items-center justify-between px-6 py-5 text-left font-semibold text-slate-900 hover:text-[#B5114A] transition-colors focus:outline-none">
                    <span>¿Qué tratamientos de ortodoncia ofrecen?</span>
                    <span class="faq-icon text-slate-400 shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
                        Contamos con brackets metálicos tradicionales, estéticos (de zafiro/cerámica) y sistemas modernos de alineadores invisibles (ortodoncia transparente), ideales para una estética discreta durante el tratamiento.
                    </p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="faq-item rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button type="button" class="faq-trigger flex w-full items-center justify-between px-6 py-5 text-left font-semibold text-slate-900 hover:text-[#B5114A] transition-colors focus:outline-none">
                    <span>¿Los implantes dentales causan dolor?</span>
                    <span class="faq-icon text-slate-400 shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
                        El procedimiento se realiza bajo anestesia local, por lo que el paciente no siente dolor. En el postoperatorio, las molestias son mínimas y perfectamente controlables con analgésicos comunes recetados por el especialista.
                    </p>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="faq-item rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button type="button" class="faq-trigger flex w-full items-center justify-between px-6 py-5 text-left font-semibold text-slate-900 hover:text-[#B5114A] transition-colors focus:outline-none">
                    <span>¿Aceptan seguros de gastos médicos?</span>
                    <span class="faq-icon text-slate-400 shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
                        Trabajamos bajo la modalidad de reembolso para la mayoría de las aseguradoras de gastos médicos mayores. Te proporcionamos toda la documentación necesaria, facturas detalladas e informe médico oficial para tu trámite.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@vite('resources/js/pages/landing/inicio.js')
