@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<header class="sticky top-0 z-50 w-full border-b border-[#F5C2D6]/20 bg-white/80 backdrop-blur-md transition-all duration-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <a href="{{ route('inicio') }}" class="flex items-center gap-2 group">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FDF1F6] text-[#B5114A] shadow-xs group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
                    <path d="M12 6c-3.31 0-6 2.69-6 6 0 1.66.67 3.16 1.76 4.24l1.42-1.42A3.98 3.98 0 0 1 8 12c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .83-.25 1.59-.68 2.24l1.42 1.42C17.33 15.16 18 13.66 18 12c0-3.31-2.69-6-6-6z" />
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">Dent<span class="text-[#B5114A]">issa</span></span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-8">
            <a href="{{ route('inicio') }}" class="text-sm font-medium transition-colors duration-200 {{ $currentRoute === 'inicio' ? 'text-[#B5114A] font-semibold' : 'text-slate-600 hover:text-[#B5114A]' }}">Inicio</a>
            <a href="{{ route('acerca') }}" class="text-sm font-medium transition-colors duration-200 {{ $currentRoute === 'acerca' ? 'text-[#B5114A] font-semibold' : 'text-slate-600 hover:text-[#B5114A]' }}">Acerca de Nosotros</a>
            <a href="{{ route('galeria') }}" class="text-sm font-medium transition-colors duration-200 {{ $currentRoute === 'galeria' ? 'text-[#B5114A] font-semibold' : 'text-slate-600 hover:text-[#B5114A]' }}">Galería</a>
            <a href="{{ route('contacto') }}" class="text-sm font-medium transition-colors duration-200 {{ $currentRoute === 'contacto' ? 'text-[#B5114A] font-semibold' : 'text-slate-600 hover:text-[#B5114A]' }}">Contacto</a>
        </nav>

        <!-- CTAs -->
        <div class="hidden md:flex items-center gap-4">
            @auth
                <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-slate-700 hover:text-[#B5114A] transition-colors">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-[#B5114A] transition-colors">Iniciar Sesión</a>
            @endauth
            
            <a href="https://wa.me/521234567890?text=Hola,%20me%20gustaria%20agendar%20una%20cita%20en%20Dentissa" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm px-5 py-2.5 shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 active:scale-98 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 fill-current" viewBox="0 0 24 24">
                    <path d="M12.004 2c-5.518 0-9.998 4.48-9.998 9.997 0 2.006.592 3.874 1.614 5.434L2.005 22l4.729-1.554c1.517.952 3.298 1.547 5.27 1.554 5.517 0 9.996-4.479 9.996-9.997S17.52 2 12.004 2zm5.727 14.156c-.23.649-1.127 1.196-1.74 1.258-.57.058-1.282.091-2.079-.166-.497-.161-1.139-.427-1.929-.769-3.373-1.458-5.549-4.887-5.719-5.112-.17-.225-1.385-1.841-1.385-3.511 0-1.67.873-2.492 1.186-2.822.313-.33.684-.412.912-.412.228 0 .456.002.656.012.207.01.488-.037.76.621.284.685.969 2.355 1.054 2.527.085.171.142.371.028.599-.114.228-.171.37-.341.57-.171.199-.356.444-.51.597-.17.17-.35.355-.15.697.199.341.884 1.455 1.897 2.355 1.306 1.161 2.409 1.522 2.752 1.693.342.171.542.142.741-.086.199-.228.855-.997 1.083-1.34.228-.342.456-.285.769-.171.314.114 1.996.941 2.338 1.112.342.171.57.257.656.4.086.143.086.827-.144 1.476z"/>
                </svg>
                <span>WhatsApp</span>
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-btn" type="button" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-[#B5114A] hover:bg-slate-50 md:hidden transition-colors" aria-label="Abrir menú">
            <svg id="menu-icon-open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg id="menu-icon-close" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-[#F5C2D6]/20 bg-white">
        <div class="space-y-1 px-4 py-3">
            <a href="{{ route('inicio') }}" class="block rounded-xl px-4 py-3 text-base font-medium {{ $currentRoute === 'inicio' ? 'bg-[#FDF1F6] text-[#B5114A]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#B5114A]' }}">Inicio</a>
            <a href="{{ route('acerca') }}" class="block rounded-xl px-4 py-3 text-base font-medium {{ $currentRoute === 'acerca' ? 'bg-[#FDF1F6] text-[#B5114A]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#B5114A]' }}">Acerca de Nosotros</a>
            <a href="{{ route('galeria') }}" class="block rounded-xl px-4 py-3 text-base font-medium {{ $currentRoute === 'galeria' ? 'bg-[#FDF1F6] text-[#B5114A]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#B5114A]' }}">Galería</a>
            <a href="{{ route('contacto') }}" class="block rounded-xl px-4 py-3 text-base font-medium {{ $currentRoute === 'contacto' ? 'bg-[#FDF1F6] text-[#B5114A]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#B5114A]' }}">Contacto</a>
            
            <hr class="my-3 border-slate-100" />

            <div class="grid gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Iniciar Sesión</a>
                @endauth
                
                <a href="https://wa.me/521234567890?text=Hola,%20me%20gustaria%20agendar%20una%20cita%20en%20Dentissa" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm px-4 py-2.5 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 fill-current" viewBox="0 0 24 24">
                        <path d="M12.004 2c-5.518 0-9.998 4.48-9.998 9.997 0 2.006.592 3.874 1.614 5.434L2.005 22l4.729-1.554c1.517.952 3.298 1.547 5.27 1.554 5.517 0 9.996-4.479 9.996-9.997S17.52 2 12.004 2zm5.727 14.156c-.23.649-1.127 1.196-1.74 1.258-.57.058-1.282.091-2.079-.166-.497-.161-1.139-.427-1.929-.769-3.373-1.458-5.549-4.887-5.719-5.112-.17-.225-1.385-1.841-1.385-3.511 0-1.67.873-2.492 1.186-2.822.313-.33.684-.412.912-.412.228 0 .456.002.656.012.207.01.488-.037.76.621.284.685.969 2.355 1.054 2.527.085.171.142.371.028.599-.114.228-.171.37-.341.57-.171.199-.356.444-.51.597-.17.17-.35.355-.15.697.199.341.884 1.455 1.897 2.355 1.306 1.161 2.409 1.522 2.752 1.693.342.171.542.142.741-.086.199-.228.855-.997 1.083-1.34.228-.342.456-.285.769-.171.314.114 1.996.941 2.338 1.112.342.171.57.257.656.4.086.143.086.827-.144 1.476z"/>
                    </svg>
                    <span>WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</header>

<script>
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
            });
        }
    });
</script>
