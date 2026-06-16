@props([
    'role' => 'usuario',
    'links' => [],
    'active' => null,
])

@php
    $normalizedRole = strtolower(trim((string) $role));
    $sidebarId = 'sidebar-'.uniqid();

    $sections = [
        'inicio' => [
            'label' => 'Inicio',
            'url' => '/dashboard',
            'icon' => 'home',
        ],
        'agenda' => [
            'label' => 'Agenda',
            'url' => '/agenda',
            'icon' => 'calendar',
        ],
        'pacientes' => [
            'label' => 'Pacientes',
            'url' => '/pacientes',
            'icon' => 'users',
        ],
        'expedientes' => [
            'label' => 'Expedientes Clinicos',
            'url' => '/expedientes-clinicos',
            'icon' => 'folder',
        ],
        'contenido' => [
            'label' => 'Contenido',
            'url' => '/contenido',
            'icon' => 'image',
        ],
        'usuarios' => [
            'label' => 'Usuarios',
            'url' => '/usuarios',
            'icon' => 'shield',
        ],
    ];

    $roleTabs = [
        'administrador' => ['inicio', 'agenda', 'pacientes', 'expedientes', 'contenido', 'usuarios'],
        'asistente' => ['inicio', 'agenda', 'expedientes'],
        'paciente' => ['inicio', 'agenda', 'expedientes'],
    ];

    $allowedTabs = $roleTabs[$normalizedRole] ?? [];

    $menuItems = collect($allowedTabs)
        ->map(function ($key) use ($sections, $links) {
            if (!isset($sections[$key])) {
                return null;
            }

            $item = $sections[$key];
            if (isset($links[$key]) && is_string($links[$key]) && $links[$key] !== '') {
                $item['url'] = $links[$key];
            }

            $item['key'] = $key;

            return $item;
        })
        ->filter()
        ->values();

    $resolvedActive = $active;

    if (!$resolvedActive) {
        $currentPath = '/'.trim(request()->path(), '/');
        $match = $menuItems->first(function ($item) use ($currentPath) {
            return $currentPath === $item['url'] || str_starts_with($currentPath.'/', rtrim($item['url'], '/').'/');
        });

        $resolvedActive = $match['key'] ?? null;
    }
@endphp

<aside id="{{ $sidebarId }}" {{ $attributes->merge(['class' => 'w-full md:w-72 lg:w-80 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm']) }}>
    <div class="mb-8 border-b border-slate-100 pb-6 px-2">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-[#E91E63] flex items-center justify-center text-white shadow-lg shadow-[#E91E63]/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-base font-bold tracking-tight text-slate-900">Dentissa</p>
                    <div class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $normalizedRole }}</p>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 md:hidden cursor-pointer"
                data-sidebar-toggle
                aria-expanded="false"
                aria-controls="{{ $sidebarId }}-menu"
                aria-label="Mostrar u ocultar menu"
            >
                <svg data-icon-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
                <svg data-icon-close xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
    </div>

    <nav id="{{ $sidebarId }}-menu" class="hidden md:block" aria-label="Sidebar navigation">
        <ul class="space-y-1.5">
            @foreach ($menuItems as $item)
                @php
                    $isActive = $resolvedActive === $item['key'];
                    $baseClasses = 'group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-bold transition-all';
                    $stateClasses = $isActive
                        ? 'bg-[#FDF1F6] text-[#B5114A] shadow-sm shadow-[#FDF1F6]'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900';
                @endphp

                <li>
                    <a href="{{ $item['url'] }}" class="{{ $baseClasses }} {{ $stateClasses }}">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors {{ $isActive ? 'bg-white text-[#B5114A] shadow-sm' : 'bg-slate-50 text-slate-400 group-hover:bg-white group-hover:text-slate-900 shadow-none group-hover:shadow-sm' }}">
                            @if ($item['icon'] === 'home')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9.5 12 3l9 6.5" />
                                    <path d="M5 10v10h14V10" />
                                </svg>
                            @elseif ($item['icon'] === 'calendar')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                            @elseif ($item['icon'] === 'users')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            @elseif ($item['icon'] === 'folder')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                                </svg>
                            @elseif ($item['icon'] === 'image')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <path d="m21 15-5-5L5 21" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
                                </svg>
                            @endif
                        </span>

                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-8 pt-6 border-t border-slate-100">
            <a
                href="{{ route('logout.page') }}"
                class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-bold text-slate-500 transition-all hover:bg-red-50 hover:text-red-600"
            >
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 group-hover:bg-white group-hover:text-red-600 group-hover:shadow-sm transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </span>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </nav>
</aside>

<script>
    (function () {
        var sidebar = document.getElementById('{{ $sidebarId }}');
        if (!sidebar) {
            return;
        }

        var toggleButton = sidebar.querySelector('[data-sidebar-toggle]');
        var menu = sidebar.querySelector('#{{ $sidebarId }}-menu');
        var openIcon = sidebar.querySelector('[data-icon-open]');
        var closeIcon = sidebar.querySelector('[data-icon-close]');

        if (!toggleButton || !menu) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            var isHidden = menu.classList.contains('hidden');

            if (isHidden) {
                menu.classList.remove('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                if (openIcon) openIcon.classList.add('hidden');
                if (closeIcon) closeIcon.classList.remove('hidden');
                return;
            }

            menu.classList.add('hidden');
            toggleButton.setAttribute('aria-expanded', 'false');
            if (openIcon) openIcon.classList.remove('hidden');
            if (closeIcon) closeIcon.classList.add('hidden');
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                menu.classList.remove('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                if (openIcon) openIcon.classList.remove('hidden');
                if (closeIcon) closeIcon.classList.add('hidden');
                return;
            }

            if (toggleButton.getAttribute('aria-expanded') !== 'true') {
                menu.classList.add('hidden');
            }
        });
    })();
</script>
