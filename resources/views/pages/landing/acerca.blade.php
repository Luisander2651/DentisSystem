@extends('layouts.landing')

@section('title', 'Acerca de Nosotros - Dentissa')

@section('content')
<div class="space-y-20 pb-20">
    <!-- Intro 50% Image and 50% Text Flex Container -->
    <section class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-12 items-center">
            <!-- Left Side: Image container (50%) -->
            <div class="w-full lg:w-1/2">
                <div class="relative overflow-hidden rounded-card border border-secondary bg-surface p-2 shadow-xl">
                    <div class="h-96 w-full rounded-box bg-gradient-to-tr from-surface via-primary-soft to-primary-soft flex items-center justify-center p-8 relative overflow-hidden">
                        <div class="absolute -left-10 -bottom-10 h-48 w-48 rounded-full bg-primary/5"></div>
                        <div class="space-y-4 text-center z-10">
                            <svg class="mx-auto h-16 w-16 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 21s7-4.4 9-8.5c1.6-3.2.4-6.8-2.6-8.1-2.1-.9-4.7-.4-6.4 1.3-1.7-1.7-4.3-2.2-6.4-1.3C2.6 5.7 1.4 9.3 3 12.5c2 4.1 9 8.5 9 8.5z" />
                                <path d="M9.2 11.2h5.6" />
                                <path d="M12 8.4v5.6" />
                            </svg>
                            <h3 class="text-xl font-bold text-ink">Comprometidos Contigo</h3>
                            <p class="text-sm text-muted max-w-xs mx-auto">Más de 10 años brindando sonrisas y salud dental integral a nuestra comunidad.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Text defining us in words (50%) -->
            <div class="w-full lg:w-1/2 space-y-6">
                <h1 class="text-base font-semibold uppercase tracking-wider text-ink">¿Quiénes Somos?</h1>
                <h2 class="text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                    Definimos la excelencia en odontología integral
                </h2>
                <p class="text-lg text-muted leading-relaxed">
                    En Dentissa, combinamos el calor humano con las tecnologías más innovadoras del sector odontológico. Creemos que una consulta dental debe ser una experiencia cómoda, tranquila y de absoluta confianza.
                </p>
                <p class="text-muted leading-relaxed">
                    Nuestro equipo se compone de especialistas certificados en ortodoncia, odontopediatría, implantología y estética dental. Nos enfocamos en diagnósticos precisos y tratamientos diseñados a la medida de tu vida.
                </p>
            </div>
        </div>
    </section>

    <!-- Nuestra Historia -->
    <section class="bg-primary-soft py-20 border-y border-secondary">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Nuestra Trayectoria</h2>
                <p class="text-3xl font-bold tracking-tight text-ink">Nuestra Historia</p>
            </div>

            <div class="relative border-l-2 border-secondary ml-4 md:ml-32 space-y-12">
                <!-- Milestone 1 -->
                <div class="relative pl-8 md:pl-10">
                    <div class="absolute -left-[11px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary ring-4 ring-surface"></div>
                    <span class="text-xs font-bold text-ink uppercase tracking-wider">Año 2016</span>
                    <h3 class="mt-1 text-lg font-bold text-ink">Fundación de Dentissa</h3>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Iniciamos operaciones con un solo consultorio y el gran sueño de cambiar el miedo al dentista por experiencias positivas y placenteras.
                    </p>
                </div>

                <!-- Milestone 2 -->
                <div class="relative pl-8 md:pl-10">
                    <div class="absolute -left-[11px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary ring-4 ring-surface"></div>
                    <span class="text-xs font-bold text-ink uppercase tracking-wider">Año 2020</span>
                    <h3 class="mt-1 text-lg font-bold text-ink">Expansión e Innovación</h3>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Ampliamos nuestras instalaciones a tres unidades dentales equipadas con radiografía digital e incorporamos especialistas de tiempo completo.
                    </p>
                </div>

                <!-- Milestone 3 -->
                <div class="relative pl-8 md:pl-10">
                    <div class="absolute -left-[11px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary ring-4 ring-surface"></div>
                    <span class="text-xs font-bold text-ink uppercase tracking-wider">Año 2026</span>
                    <h3 class="mt-1 text-lg font-bold text-ink">Liderazgo Digital</h3>
                    <p class="mt-2 text-sm leading-6 text-muted">
                        Hoy somos referentes de odontología digital en la región, implementando agendas automatizadas, expedientes integrados y tratamientos premium guiados por computadora.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Nuestro Equipo (3 personas, una al lado de la otra) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
            <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Profesionales Certificados</h2>
            <p class="text-3xl font-bold tracking-tight text-ink">Nuestro Equipo</p>
            <p class="text-muted">Conoce a los especialistas dedicados a mantener tu sonrisa saludable.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Member 1 -->
            <div class="group rounded-card border border-line bg-surface p-6 text-center shadow-xs transition duration-300 hover:shadow-md">
                <!-- Avatar column flex -->
                <div class="flex flex-col items-center">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-tr from-surface to-secondary text-ink font-bold text-3xl shadow-inner group-hover:scale-105 transition-transform duration-200">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="3" />
                            <path d="M7 21c1.2-3.2 3.1-5 5-5s3.8 1.8 5 5" />
                        </svg>
                    </div>
                    <div class="mt-6 flex flex-col items-center text-center space-y-2">
                        <h3 class="text-lg font-bold text-ink">Dra. Sofía Mendoza</h3>
                        <p class="text-xs font-bold text-ink uppercase tracking-wider bg-primary-soft px-3 py-1 rounded-full">
                            12 años de experiencia
                        </p>
                        <p class="text-sm italic text-muted px-4">
                            "Diseñando sonrisas que transforman vidas día con día."
                        </p>
                    </div>
                </div>
            </div>

            <!-- Member 2 -->
            <div class="group rounded-card border border-line bg-surface p-6 text-center shadow-xs transition duration-300 hover:shadow-md">
                <div class="flex flex-col items-center">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-tr from-surface to-secondary text-ink font-bold text-3xl shadow-inner group-hover:scale-105 transition-transform duration-200">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="3" />
                            <path d="M7 21c1.2-3.2 3.1-5 5-5s3.8 1.8 5 5" />
                            <path d="M9.5 11.5l-.8 1.5" />
                        </svg>
                    </div>
                    <div class="mt-6 flex flex-col items-center text-center space-y-2">
                        <h3 class="text-lg font-bold text-ink">Dr. Alejandro Ríos</h3>
                        <p class="text-xs font-bold text-ink uppercase tracking-wider bg-primary-soft px-3 py-1 rounded-full">
                            8 años de experiencia
                        </p>
                        <p class="text-sm italic text-muted px-4">
                            "Especialista en restaurar la funcionalidad y la autoconfianza."
                        </p>
                    </div>
                </div>
            </div>

            <!-- Member 3 -->
            <div class="group rounded-card border border-line bg-surface p-6 text-center shadow-xs transition duration-300 hover:shadow-md">
                <div class="flex flex-col items-center">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-tr from-surface to-secondary text-ink font-bold text-3xl shadow-inner group-hover:scale-105 transition-transform duration-200">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="3" />
                            <path d="M7 21c1.2-3.2 3.1-5 5-5s3.8 1.8 5 5" />
                            <path d="M10 11h4" />
                        </svg>
                    </div>
                    <div class="mt-6 flex flex-col items-center text-center space-y-2">
                        <h3 class="text-lg font-bold text-ink">Dra. Valeria Esparza</h3>
                        <p class="text-xs font-bold text-ink uppercase tracking-wider bg-primary-soft px-3 py-1 rounded-full">
                            10 años de experiencia
                        </p>
                        <p class="text-sm italic text-muted px-4">
                            "Haciendo de la visita dental de los niños una aventura divertida."
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Valores (Format grid 5-6 values max) -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
            <h2 class="text-base font-semibold uppercase tracking-wider text-ink">Nuestros Pilares</h2>
            <p class="text-3xl font-bold tracking-tight text-ink">Valores de la Clínica</p>
            <p class="text-muted">Nos guiamos bajo principios éticos para garantizar tu bienestar.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Valor 1 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 12l3-3 2 2 5-5" />
                        <path d="M4 14l4 4 12-12" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Empatía</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Entendemos tus temores y necesidades, brindando un trato cálido y humano en todo momento.
                    </p>
                </div>
            </div>

            <!-- Valor 2 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l7 4v5c0 4.4-2.9 7.9-7 9-4.1-1.1-7-4.6-7-9V7l7-4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Higiene y Seguridad</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Cumplimos estrictos procesos de esterilización para tu total tranquilidad y seguridad médica.
                    </p>
                </div>
            </div>

            <!-- Valor 3 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="7" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Honestidad</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Diagnosticamos únicamente lo que necesitas, explicándote cada procedimiento con transparencia.
                    </p>
                </div>
            </div>

            <!-- Valor 4 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 4h12l4 6-10 10L2 10z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Excelencia</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Cuidamos cada detalle para garantizar tratamientos de alta durabilidad y óptima estética.
                    </p>
                </div>
            </div>

            <!-- Valor 5 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18h6" />
                        <path d="M10 22h4" />
                        <path d="M8 14a6 6 0 1 1 8 0c-.8.7-1.2 1.5-1.4 2.5H9.4c-.2-1-.6-1.8-1.4-2.5z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Vanguardia</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Nos mantenemos actualizados en conocimientos y tecnologías odontológicas de última generación.
                    </p>
                </div>
            </div>

            <!-- Valor 6 -->
            <div class="rounded-card border border-secondary bg-surface p-6 flex gap-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-ink shadow-xs">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 21s-7-4.35-9.33-8.35C.95 9.44 2.12 6.5 5.05 5.4c1.76-.66 3.74-.2 4.95 1.11 1.21-1.31 3.19-1.77 4.95-1.11 2.93 1.1 4.1 4.04 2.38 7.25C19 16.65 12 21 12 21z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-ink">Compromiso</h3>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Nos comprometemos con la salud dental a largo plazo de tu familia, desde la prevención hasta la restauración.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
