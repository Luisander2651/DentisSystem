@props([
    'role' => 'usuario',
    'links' => [],
    'active' => null,
])

@php
    $normalizedRole = strtolower(trim((string) $role));
    $sidebarId = 'sidebar-'.uniqid();
    $roleLabel = ['administrador' => 'Administrador', 'asistente' => 'Asistente', 'doctor' => 'Doctor', 'paciente' => 'Paciente'][$normalizedRole] ?? 'Usuario';

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
        'tratamientos' => [
            'label' => 'Tratamientos',
            'url' => '/tratamientos',
            'icon' => 'treatment',
        ],
        'pacientes' => [
            'label' => 'Pacientes',
            'url' => '/pacientes',
            'icon' => 'users',
        ],
        'expedientes' => [
            'label' => 'Expedientes clínicos',
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
        'administrador' => ['inicio', 'agenda', 'tratamientos', 'pacientes', 'expedientes', 'contenido', 'usuarios'],
        'asistente' => ['inicio', 'expedientes'],
        'doctor' => ['inicio', 'expedientes'],
        'paciente' => ['inicio'],
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

<aside id="{{ $sidebarId }}" data-sidebar-root {{ $attributes->merge(['class' => 'w-full md:w-72 lg:w-80 rounded-card border border-line bg-surface p-3 md:p-6 shadow-sm']) }}>
    <div class="px-1 md:mb-6 md:border-b md:border-line md:px-2 md:pb-6">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-ui.brand><span data-role-label="menu">{{ $roleLabel }}</span></x-ui.brand>
            </div>

            <button
                type="button"
                class="inline-flex size-control items-center justify-center rounded-control border border-field bg-surface text-ink transition hover:bg-canvas md:hidden cursor-pointer"
                data-sidebar-toggle
                aria-expanded="false"
                aria-controls="{{ $sidebarId }}-menu"
                aria-label="Abrir o cerrar el menú"
            >
                <svg data-icon-open aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
                <svg data-icon-close aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
    </div>

    <nav id="{{ $sidebarId }}-menu" data-sidebar-menu class="hidden pt-3 md:block md:pt-0" aria-label="Menú principal">
        <ul class="flex flex-col gap-1">
            @foreach ($menuItems as $item)
                @php
                    $isActive = $resolvedActive === $item['key'];
                    $baseClasses = 'group flex min-h-control items-center gap-3 rounded-control border-l-4 px-3 py-1 text-sm font-semibold transition-colors';
                    $stateClasses = $isActive
                        ? 'border-primary bg-primary-soft text-ink'
                        : 'border-transparent text-muted hover:bg-canvas hover:text-ink';
                @endphp

                <li>
                    <a href="{{ $item['url'] }}" class="{{ $baseClasses }} {{ $stateClasses }}" @if ($isActive) aria-current="page" @endif>
                        <span class="inline-flex size-9 items-center justify-center" aria-hidden="true">
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
                            @elseif ($item['icon'] === 'treatment')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
                                    <path d="M9 13h6" />
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

        <div class="mt-4 border-t border-line pt-4">
            <a
                href="{{ route('logout.page') }}"
                class="group flex min-h-control items-center gap-3 rounded-control border-l-4 border-transparent px-3 py-1 text-sm font-semibold text-muted transition-colors hover:bg-danger-soft hover:text-danger"
            >
                <span class="inline-flex size-9 items-center justify-center" aria-hidden="true">
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
