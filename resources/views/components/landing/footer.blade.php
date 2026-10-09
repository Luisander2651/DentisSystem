@php
    $whatsapp = 'https://wa.me/521234567890?text=Hola,%20me%20gustar%C3%ADa%20agendar%20una%20cita%20en%20Dentissa';
    $social = 'inline-flex size-control items-center justify-center rounded-control border border-field bg-surface text-ink transition-colors hover:bg-canvas';
    $link = 'flex min-h-control items-center text-sm text-muted underline-offset-4 transition-colors hover:text-ink hover:underline';
@endphp

<footer class="mt-auto border-t border-line bg-surface">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4 lg:gap-12">
            <!-- Brand & Info -->
            <div class="space-y-4 md:col-span-1">
                <a href="{{ route('inicio') }}" class="inline-flex rounded-control">
                    <x-ui.brand variant="logo" class="w-56" />
                </a>
                <p class="text-sm leading-6 text-muted">
                    Cuidando de tu sonrisa con tecnología avanzada, especialistas dedicados y un ambiente cómodo para toda la familia.
                </p>
                <div class="flex items-center gap-3">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="{{ $social }}" aria-label="Facebook">
                        <svg aria-hidden="true" class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="{{ $social }}" aria-label="Instagram">
                        <svg aria-hidden="true" class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                        </svg>
                    </a>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="{{ $social }}" aria-label="WhatsApp">
                        <svg aria-hidden="true" class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12.004 2c-5.518 0-9.998 4.48-9.998 9.997 0 2.006.592 3.874 1.614 5.434L2.005 22l4.729-1.554c1.517.952 3.298 1.547 5.27 1.554 5.517 0 9.996-4.479 9.996-9.997S17.52 2 12.004 2zm5.727 14.156c-.23.649-1.127 1.196-1.74 1.258-.57.058-1.282.091-2.079-.166-.497-.161-1.139-.427-1.929-.769-3.373-1.458-5.549-4.887-5.719-5.112-.17-.225-1.385-1.841-1.385-3.511 0-1.67.873-2.492 1.186-2.822.313-.33.684-.412.912-.412.228 0 .456.002.656.012.207.01.488-.037.76.621.284.685.969 2.355 1.054 2.527.085.171.142.371.028.599-.114.228-.171.37-.341.57-.171.199-.356.444-.51.597-.17.17-.35.355-.15.697.199.341.884 1.455 1.897 2.355 1.306 1.161 2.409 1.522 2.752 1.693.342.171.542.142.741-.086.199-.228.855-.997 1.083-1.34.228-.342.456-.285.769-.171.314.114 1.996.941 2.338 1.112.342.171.57.257.656.4.086.143.086.827-.144 1.476z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Navegación</h3>
                <ul>
                    <li><a href="{{ route('inicio') }}" class="{{ $link }}">Inicio</a></li>
                    <li><a href="{{ route('acerca') }}" class="{{ $link }}">Acerca de Nosotros</a></li>
                    <li><a href="{{ route('galeria') }}" class="{{ $link }}">Galería de Fotos</a></li>
                    <li><a href="{{ route('contacto') }}" class="{{ $link }}">Contacto y Ubicación</a></li>
                </ul>
            </div>

            <!-- Contact Details -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Contacto</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li class="flex items-start gap-2.5">
                        <span data-accent class="mt-0.5 shrink-0 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span>Av. Universidad 1200, Colonia Centro, Ciudad de México, CP 03100</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span data-accent class="shrink-0 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 00.996.808H12a1 1 0 00.78-.395l1.62-2.16a1 1 0 011.085-.355L19 5a2 2 0 012 2v3a9 9 0 01-9 9 9 9 0 01-9-9V5z" />
                            </svg>
                        </span>
                        <span>+52 55 1234 5678</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span data-accent class="shrink-0 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span>contacto@dentissa.com</span>
                    </li>
                </ul>
            </div>

            <!-- Business Hours -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Horarios</h3>
                <ul class="space-y-2 text-sm text-muted">
                    <li class="flex justify-between">
                        <span>Lunes - Viernes:</span>
                        <span class="font-medium text-ink">9:00 AM - 7:00 PM</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Sábado:</span>
                        <span class="font-medium text-ink">9:00 AM - 2:00 PM</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Domingo:</span>
                        <span class="font-medium text-danger">Cerrado</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-2 border-t border-line pt-6 sm:flex-row">
            <p class="text-min text-muted">
                &copy; {{ date('Y') }} Dentissa. Todos los derechos reservados.
            </p>
            <p class="flex gap-4 text-min">
                <a href="#" class="inline-flex min-h-control items-center text-muted underline-offset-4 hover:text-ink hover:underline">Aviso de Privacidad</a>
                <a href="#" class="inline-flex min-h-control items-center text-muted underline-offset-4 hover:text-ink hover:underline">Términos de Servicio</a>
            </p>
        </div>
    </div>
</footer>
