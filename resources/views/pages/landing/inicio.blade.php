@extends('layouts.landing')

@section('title', 'Dentissa - Clínica Dental Premium')

@section('content')
<div class="space-y-20 pb-20">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-primary-soft py-24 sm:py-32">
        <div class="absolute inset-y-0 right-1/2 -z-10 -mr-96 w-[200%] origin-top-right skew-x-[-30deg] bg-surface shadow-xs ring-1 ring-line sm:-mr-80 lg:-mr-96" aria-hidden="true"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
                <!-- Text Content -->
                <div class="max-w-2xl space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary-soft px-4 py-2 text-sm font-semibold text-ink">
                        <svg class="h-4 w-4 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                        </svg>
                        <span>Sonrisas sanas y naturales</span>
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-ink sm:text-6xl font-sans">
                        La mejor tecnología para tu <span class="text-ink">salud dental</span>
                    </h1>
                    <p class="text-lg leading-8 text-muted">
                        En Dentissa nos apasiona crear sonrisas hermosas y saludables. Ofrecemos tratamientos dentales integrales de alta calidad en un ambiente cálido, seguro y diseñado para tu comodidad.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <a href="https://wa.me/521234567890?text=Hola,%20me%20gustaria%20agendar%20una%20citas" target="_blank" rel="noopener noreferrer">
                            <x-ui.button variant="primary" class="w-full sm:w-auto">
                                Agenda una Cita
                            </x-ui.button>
                        </a>
                        <a href="{{ route('contacto') }}">
                            <x-ui.button variant="secondary" class="w-full sm:w-auto">
                                Ver Ubicación
                            </x-ui.button>
                        </a>
                    </div>
                </div>

                <!-- Hero Image with floating stats -->
                <div class="relative mx-auto max-w-md lg:max-w-none lg:mx-0">
                    <div class="relative overflow-hidden rounded-card border border-secondary bg-surface p-2 shadow-xl">
                        <!-- We use a premium CSS dentist scene with gradients instead of standard empty placeolders -->
                        <div class="h-80 w-full rounded-box bg-gradient-to-br from-surface via-primary-soft to-primary flex items-center justify-center p-8 text-center relative overflow-hidden">
                            <!-- Background abstract circle -->
                            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary/5"></div>
                            
                            <div class="space-y-4 z-10">
                                <svg class="mx-auto h-16 w-16 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M7 3c-1.7 0-3 1.4-3 3.1 0 1.2.6 2.4 1.2 3.5.7 1.2 1.4 2.8 1.4 5.7 0 3 1.4 6.7 2.8 6.7 1.3 0 1.8-1.9 2.6-4.5.4-1.3.8-2.7 2-2.7s1.6 1.4 2 2.7c.8 2.6 1.3 4.5 2.6 4.5 1.4 0 2.8-3.7 2.8-6.7 0-2.9.7-4.5 1.4-5.7.6-1.1 1.2-2.3 1.2-3.5C20 4.4 18.7 3 17 3c-1.3 0-2.1.5-2.9 1.2-.8.7-1.5 1.3-2.1 1.3s-1.3-.6-2.1-1.3C9.1 3.5 8.3 3 7 3z" />
                                </svg>
                                <p class="text-xl font-bold text-ink">Atención dental de calidad</p>
                                <p class="text-sm text-ink max-w-xs mx-auto">Equipamiento moderno y especialistas certificados listos para cuidar de ti.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badges -->
                    <div class="absolute left-2 top-8 sm:-left-6 rounded-box bg-surface p-4 shadow-md border border-line flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-control bg-primary-soft text-ink font-bold">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-ink">100% Seguro</p>
                            <p class="text-min text-muted">Normas COFEPRIS</p>
                        </div>
                    </div>

                    <div class="absolute right-2 bottom-8 sm:-right-6 rounded-box bg-surface p-4 shadow-md border border-line flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-control bg-primary-soft text-ink font-bold">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-ink">5.0 Estrellas</p>
                            <p class="text-min text-muted">Opiniones de pacientes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
            <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Nuestros Servicios</h2>
            <p class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">Tratamientos odontológicos especializados</p>
            <p class="text-muted">Diseñamos soluciones personalizadas para mejorar tu estética dental y salud bucal.</p>
        </div>

        <!-- 3up / 2down format using responsive grid -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 justify-center">
            <!-- Service 1: Limpieza -->
            <div class="col-span-1 md:col-span-2 group rounded-card border border-line bg-surface p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-secondary">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-box bg-primary-soft text-ink transition-colors group-hover:bg-primary group-hover:text-ink">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.8 5.4L19 10.2l-5.2 1.8L12 17.4l-1.8-5.4L5 10.2l5.2-1.8L12 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-ink">Limpieza y Prevención</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Limpieza profunda con ultrasonido para eliminar sarro, prevenir caries y mantener tus encías completamente sanas.
                </p>
            </div>

            <!-- Service 2: Ortodoncia -->
            <div class="col-span-1 md:col-span-2 group rounded-card border border-line bg-surface p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-secondary">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-box bg-primary-soft text-ink transition-colors group-hover:bg-primary group-hover:text-ink">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M8 15c1.2-1 2.5-1.5 4-1.5s2.8.5 4 1.5" />
                        <path d="M9 10h.01" />
                        <path d="M15 10h.01" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-ink">Ortodoncia</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Alinea tus dientes con brackets tradicionales o alineadores invisibles. Ortodoncia de vanguardia para niños y adultos.
                </p>
            </div>

            <!-- Service 3: Implantes -->
            <div class="col-span-1 md:col-span-2 group rounded-card border border-line bg-surface p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-secondary">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-box bg-primary-soft text-ink transition-colors group-hover:bg-primary group-hover:text-ink">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 7l10 10" />
                        <path d="M6 8l2-2 2 2-2 2z" />
                        <path d="M14 16l2-2 2 2-2 2z" />
                        <path d="M5 19l3-3" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-ink">Implantes Dentales</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Recupera la funcionalidad y estética de tu boca reemplazando piezas perdidas con implantes de titanio altamente duraderos.
                </p>
            </div>

            <!-- Service 4: Blanqueamiento -->
            <div class="col-span-1 md:col-span-3 lg:col-start-2 lg:col-span-2 group rounded-card border border-line bg-surface p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-secondary">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-box bg-primary-soft text-ink transition-colors group-hover:bg-primary group-hover:text-ink">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 4h12l4 6-10 10L2 10z" />
                        <path d="M9 4l3 16 3-16" />
                        <path d="M2 10h20" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-ink">Blanqueamiento Dental</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Aclara el tono de tus dientes de forma segura y rápida con tecnología láser. Resultados visibles en una sola sesión.
                </p>
            </div>

            <!-- Service 5: Odontopediatria -->
            <div class="col-span-1 md:col-span-3 lg:col-span-2 group rounded-card border border-line bg-surface p-8 shadow-sm transition-all duration-300 hover:shadow-md hover:border-secondary">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-box bg-primary-soft text-ink transition-colors group-hover:bg-primary group-hover:text-ink">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="3" />
                        <path d="M7 20c1.2-3 3.1-5 5-5s3.8 2 5 5" />
                        <path d="M9 11l-1 2" />
                        <path d="M15 11l1 2" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-ink">Odontopediatría</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Atención dental especializada y amigable para los más pequeños de la casa, creando hábitos saludables desde la infancia.
                </p>
            </div>
        </div>
    </section>

    <!-- Promotions Section (Dynamic Backend load) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-baseline justify-between gap-4 border-b border-secondary pb-5 mb-10">
            <div>
                <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Promociones Especiales</h2>
                <p class="text-3xl font-bold tracking-tight text-ink mt-1">Aprovecha nuestras ofertas del mes</p>
            </div>
            <a href="https://wa.me/521234567890" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-control items-center text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">Preguntar por otras promociones &rarr;</a>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($promotions as $promotion)
                <article class="rounded-card border border-line bg-surface p-6 shadow-xs hover:shadow-md transition-all duration-300">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-ink">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 7H4v4h16V7z" />
                                    <path d="M6 11v8h12v-8" />
                                    <path d="M12 7v12" />
                                </svg>
                                <span>Promoción</span>
                            </span>
                            <h3 class="mt-3 text-lg font-bold text-ink wrap-break-word">{{ $promotion['name'] }}</h3>
                        </div>
                        <span class="shrink-0 rounded-box bg-warning-soft px-3 py-2 text-center shadow-xs border border-warning">
                            <span class="block text-sm font-extrabold text-warning">{{ $promotion['discount_percentage'] }}%</span>
                            <span class="block text-min uppercase tracking-wider font-bold text-warning">Desc</span>
                        </span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-muted wrap-break-word line-clamp-3">{{ $promotion['description'] }}</p>
                    <div class="mt-6 border-t border-line pt-4 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs text-muted">
                            <span>Válido del:</span>
                            <span class="font-semibold text-muted">{{ \Illuminate\Support\Carbon::parse($promotion['start_date'])->translatedFormat('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted">
                            <span>Al:</span>
                            <span class="font-semibold text-muted">{{ \Illuminate\Support\Carbon::parse($promotion['end_date'])->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="md:col-span-2 lg:col-span-3 py-12 text-center bg-canvas rounded-card border border-line">
                    <svg class="mx-auto h-10 w-10 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 7H4v4h16V7z" />
                        <path d="M6 11v8h12v-8" />
                        <path d="M12 7v12" />
                        <path d="M12 7c-1.7 0-3-1.1-3-2.5S10.3 2 12 7z" />
                        <path d="M12 7c1.7 0 3-1.1 3-2.5S13.7 2 12 7z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-ink">Sin promociones por el momento</h3>
                    <p class="mt-2 text-xs text-muted">Suscríbete o contáctanos para conocer sobre futuros descuentos.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Certifications Section (Dynamic Backend load) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-baseline justify-between gap-4 border-b border-secondary pb-5 mb-10">
            <div>
                <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Certificaciones</h2>
                <p class="text-3xl font-bold tracking-tight text-ink mt-1">Respaldos y sellos que fortalecen nuestra atención</p>
            </div>
            <p class="text-sm font-medium text-muted">Estándares, reconocimientos y validaciones institucionales.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($certifications as $certification)
                <article class="overflow-hidden rounded-card border border-secondary bg-surface shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                    <div class="aspect-[4/3] bg-gradient-to-br from-primary-soft to-surface p-4">
                        <div class="flex h-full items-center justify-center overflow-hidden rounded-box border border-dashed border-secondary bg-surface/80">
                            @if (!empty($certification['image_url']))
                                <img src="{{ $certification['image_url'] }}" alt="{{ $certification['name'] }}" class="h-full w-full object-cover" loading="lazy" />
                            @else
                                <div class="px-6 text-center"><span class="text-4xl">🏅</span><p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-ink">Certificación</p></div>
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-ink">Reconocimiento</span>
                        <h3 class="mt-3 text-lg font-bold text-ink wrap-break-word">{{ $certification['name'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-muted wrap-break-word line-clamp-3">{{ $certification['description'] }}</p>
                        <div class="mt-5 flex items-center justify-between border-t border-line pt-4 text-xs text-muted">
                            <span class="font-semibold uppercase tracking-wider text-ink">Fecha</span>
                            <span class="font-medium text-muted">{{ \Illuminate\Support\Carbon::parse($certification['date'])->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="md:col-span-2 lg:col-span-3 py-12 text-center bg-surface rounded-card border border-secondary">
                    <svg class="mx-auto h-10 w-10 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 15l-4.5 2.5 1-5L5 9.5l5.1-.5L12 4l1.9 5 5.1.5-3.5 3 1 5z" />
                        <path d="M12 15v5" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-ink">Sin certificaciones por el momento</h3>
                    <p class="mt-2 text-xs text-muted">Muy pronto compartiremos los sellos que avalan nuestra práctica clínica.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Testimonials Section (Dynamic Backend load) -->
    <section class="bg-primary-soft py-20 text-ink rounded-card mx-4 sm:mx-6 lg:mx-8 shadow-xl shadow-primary/20 border border-secondary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Testimonios</h2>
                <p class="text-3xl font-bold tracking-tight sm:text-4xl text-ink">Lo que dicen nuestros pacientes</p>
                <p class="text-muted">Nuestra prioridad es tu comodidad y satisfacción. Estas son algunas de sus experiencias.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($testimonials as $testimonial)
                    <div class="rounded-card border border-secondary bg-surface p-6 flex flex-col justify-between shadow-sm hover:-translate-y-1 hover:shadow-md transition duration-300">
                        <div>
                            <div class="mb-4 flex items-center gap-1 text-warning" role="img" aria-label="5 de 5 estrellas">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>
                            </div>
                            <p class="text-sm leading-6 text-muted italic wrap-break-word">"{{ $testimonial['description'] }}"</p>
                        </div>
                        <div class="mt-6 border-t border-line pt-4 flex items-center justify-between">
                            <span class="text-sm font-bold text-ink">{{ $testimonial['author'] }}</span>
                            <span class="text-min text-muted uppercase font-semibold">{{ \Illuminate\Support\Carbon::parse($testimonial['created_at'])->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 lg:col-span-3 py-12 text-center text-muted">
                        <svg class="mx-auto h-10 w-10 text-ink" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" />
                        </svg>
                        <p class="mt-4 text-sm">Próximamente estaremos compartiendo las opiniones de nuestros pacientes.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-4 mb-12">
            <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Preguntas Frecuentes</h2>
            <p class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">Resolveremos tus dudas</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ 1 -->
            <div class="faq-item rounded-box border border-line bg-surface overflow-hidden transition-all duration-200">
                <button type="button" data-pressable aria-expanded="false" class="faq-trigger flex min-h-control w-full items-center justify-between rounded-box px-6 py-5 text-left font-semibold text-ink">
                    <span>¿Cada cuánto tiempo debo ir al dentista para una limpieza?</span>
                    <span aria-hidden="true" class="faq-icon text-muted shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div inert class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-muted leading-relaxed border-t border-line pt-3">
                        Se recomienda realizar una limpieza dental profesional cada 6 meses. Esto ayuda a prevenir la acumulación de sarro, diagnosticar a tiempo posibles caries y mantener las encías en óptimo estado.
                    </p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="faq-item rounded-box border border-line bg-surface overflow-hidden transition-all duration-200">
                <button type="button" data-pressable aria-expanded="false" class="faq-trigger flex min-h-control w-full items-center justify-between rounded-box px-6 py-5 text-left font-semibold text-ink">
                    <span>¿Qué tratamientos de ortodoncia ofrecen?</span>
                    <span aria-hidden="true" class="faq-icon text-muted shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div inert class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-muted leading-relaxed border-t border-line pt-3">
                        Contamos con brackets metálicos tradicionales, estéticos (de zafiro/cerámica) y sistemas modernos de alineadores invisibles (ortodoncia transparente), ideales para una estética discreta durante el tratamiento.
                    </p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="faq-item rounded-box border border-line bg-surface overflow-hidden transition-all duration-200">
                <button type="button" data-pressable aria-expanded="false" class="faq-trigger flex min-h-control w-full items-center justify-between rounded-box px-6 py-5 text-left font-semibold text-ink">
                    <span>¿Los implantes dentales causan dolor?</span>
                    <span aria-hidden="true" class="faq-icon text-muted shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div inert class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-muted leading-relaxed border-t border-line pt-3">
                        El procedimiento se realiza bajo anestesia local, por lo que el paciente no siente dolor. En el postoperatorio, las molestias son mínimas y perfectamente controlables con analgésicos comunes recetados por el especialista.
                    </p>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="faq-item rounded-box border border-line bg-surface overflow-hidden transition-all duration-200">
                <button type="button" data-pressable aria-expanded="false" class="faq-trigger flex min-h-control w-full items-center justify-between rounded-box px-6 py-5 text-left font-semibold text-ink">
                    <span>¿Aceptan seguros de gastos médicos?</span>
                    <span aria-hidden="true" class="faq-icon text-muted shrink-0 ml-4 font-bold text-lg transition-transform duration-200">&plus;</span>
                </button>
                <div inert class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <p class="px-6 pb-5 text-sm text-muted leading-relaxed border-t border-line pt-3">
                        Trabajamos bajo la modalidad de reembolso para la mayoría de las aseguradoras de gastos médicos mayores. Te proporcionamos toda la documentación necesaria, facturas detalladas e informe médico oficial para tu trámite.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@vite('resources/js/pages/landing/inicio.js')
