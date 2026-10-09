@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $whatsapp = 'https://wa.me/521234567890?text=Hola,%20me%20gustar%C3%ADa%20agendar%20una%20cita%20en%20Dentissa';

    $links = [
        'inicio' => 'Inicio',
        'acerca' => 'Acerca de Nosotros',
        'galeria' => 'Galería',
        'contacto' => 'Contacto',
    ];
@endphp

<header class="sticky top-0 z-50 w-full border-b border-line bg-surface/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <!-- Logo -->
        <a href="{{ route('inicio') }}" class="inline-flex min-h-control items-center rounded-control">
            <x-ui.brand />
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden items-center gap-8 md:flex" aria-label="Secciones del sitio">
            @foreach ($links as $route => $label)
                <a
                    href="{{ route($route) }}"
                    @if ($currentRoute === $route) aria-current="page" @endif
                    class="inline-flex min-h-control items-center text-sm font-medium text-muted transition-colors hover:text-ink aria-[current=page]:font-semibold aria-[current=page]:text-ink aria-[current=page]:underline aria-[current=page]:decoration-primary aria-[current=page]:decoration-2 aria-[current=page]:underline-offset-8"
                >{{ $label }}</a>
            @endforeach
        </nav>

        <!-- CTAs -->
        <div class="hidden items-center gap-4 md:flex">
            @auth
                <a href="{{ url('/dashboard') }}" class="inline-flex min-h-control items-center text-sm font-semibold text-ink underline-offset-4 hover:underline">Mi panel</a>
            @else
                <a href="{{ route('login') }}" class="inline-flex min-h-control items-center text-sm font-semibold text-ink underline-offset-4 hover:underline">Iniciar Sesión</a>
            @endauth

            <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-control items-center justify-center gap-2 rounded-control bg-whatsapp px-5 text-sm font-semibold text-ink transition-opacity hover:opacity-90">
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4.5 w-4.5 fill-current" viewBox="0 0 24 24">
                    <path d="M12.004 2c-5.518 0-9.998 4.48-9.998 9.997 0 2.006.592 3.874 1.614 5.434L2.005 22l4.729-1.554c1.517.952 3.298 1.547 5.27 1.554 5.517 0 9.996-4.479 9.996-9.997S17.52 2 12.004 2zm5.727 14.156c-.23.649-1.127 1.196-1.74 1.258-.57.058-1.282.091-2.079-.166-.497-.161-1.139-.427-1.929-.769-3.373-1.458-5.549-4.887-5.719-5.112-.17-.225-1.385-1.841-1.385-3.511 0-1.67.873-2.492 1.186-2.822.313-.33.684-.412.912-.412.228 0 .456.002.656.012.207.01.488-.037.76.621.284.685.969 2.355 1.054 2.527.085.171.142.371.028.599-.114.228-.171.37-.341.57-.171.199-.356.444-.51.597-.17.17-.35.355-.15.697.199.341.884 1.455 1.897 2.355 1.306 1.161 2.409 1.522 2.752 1.693.342.171.542.142.741-.086.199-.228.855-.997 1.083-1.34.228-.342.456-.285.769-.171.314.114 1.996.941 2.338 1.112.342.171.57.257.656.4.086.143.086.827-.144 1.476z"/>
                </svg>
                <span>WhatsApp</span>
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <x-ui.button variant="icon" id="mobile-menu-btn" label="Abrir menú" aria-expanded="false" aria-controls="mobile-menu" class="md:hidden">
            <svg id="menu-icon-open" aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg id="menu-icon-close" aria-hidden="true" class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </x-ui.button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden border-t border-line bg-surface md:hidden">
        <nav class="flex flex-col gap-1 px-4 py-3" aria-label="Secciones del sitio">
            @foreach ($links as $route => $label)
                <a
                    href="{{ route($route) }}"
                    @if ($currentRoute === $route) aria-current="page" @endif
                    class="flex min-h-control items-center rounded-control border-l-4 border-transparent px-4 text-base font-medium text-muted hover:bg-canvas hover:text-ink aria-[current=page]:border-primary aria-[current=page]:bg-primary-soft aria-[current=page]:font-semibold aria-[current=page]:text-ink"
                >{{ $label }}</a>
            @endforeach

            <hr class="my-2 border-line" />

            <div class="grid gap-2">
                @auth
                    <x-ui.button :href="url('/dashboard')">Mi panel</x-ui.button>
                @else
                    <x-ui.button :href="route('login')">Iniciar Sesión</x-ui.button>
                @endauth

                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="flex min-h-control items-center justify-center gap-2 rounded-control bg-whatsapp px-4 text-sm font-semibold text-ink">
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4.5 w-4.5 fill-current" viewBox="0 0 24 24">
                        <path d="M12.004 2c-5.518 0-9.998 4.48-9.998 9.997 0 2.006.592 3.874 1.614 5.434L2.005 22l4.729-1.554c1.517.952 3.298 1.547 5.27 1.554 5.517 0 9.996-4.479 9.996-9.997S17.52 2 12.004 2zm5.727 14.156c-.23.649-1.127 1.196-1.74 1.258-.57.058-1.282.091-2.079-.166-.497-.161-1.139-.427-1.929-.769-3.373-1.458-5.549-4.887-5.719-5.112-.17-.225-1.385-1.841-1.385-3.511 0-1.67.873-2.492 1.186-2.822.313-.33.684-.412.912-.412.228 0 .456.002.656.012.207.01.488-.037.76.621.284.685.969 2.355 1.054 2.527.085.171.142.371.028.599-.114.228-.171.37-.341.57-.171.199-.356.444-.51.597-.17.17-.35.355-.15.697.199.341.884 1.455 1.897 2.355 1.306 1.161 2.409 1.522 2.752 1.693.342.171.542.142.741-.086.199-.228.855-.997 1.083-1.34.228-.342.456-.285.769-.171.314.114 1.996.941 2.338 1.112.342.171.57.257.656.4.086.143.086.827-.144 1.476z"/>
                    </svg>
                    <span>WhatsApp</span>
                </a>
            </div>
        </nav>
    </div>
</header>

<script nonce="{{ Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        var menuBtn = document.getElementById('mobile-menu-btn');
        var mobileMenu = document.getElementById('mobile-menu');
        var openIcon = document.getElementById('menu-icon-open');
        var closeIcon = document.getElementById('menu-icon-close');

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function () {
                var isHidden = mobileMenu.classList.contains('hidden');

                mobileMenu.classList.toggle('hidden', !isHidden);
                openIcon.classList.toggle('hidden', isHidden);
                closeIcon.classList.toggle('hidden', !isHidden);
                menuBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                menuBtn.setAttribute('aria-label', isHidden ? 'Cerrar menú' : 'Abrir menú');
            });
        }
    });
</script>
