@extends('layouts.admin')

@section('title', 'Tratamientos - Dentissa')

@section('content')
<div class="space-y-6">
    <x-ui.page-hero
        title="Tratamientos"
        description="Administra el catálogo de tratamientos de la clínica."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
            </svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-ui.button variant="primary" data-treatments-create-open class="w-full sm:w-auto">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Nuevo tratamiento
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-hero>

    <div class="grid grid-cols-3 gap-2 md:gap-3">
        <x-ui.stat label="Total"><span data-treatments-total-count>0</span></x-ui.stat>
        <x-ui.stat label="Con duración"><span data-treatments-with-time-count>0</span></x-ui.stat>
        <x-ui.stat label="Promedio"><span data-treatments-average-time>0 min</span></x-ui.stat>
    </div>

    <div class="relative">
        <label for="treatments-search" class="mb-1.5 block text-sm font-semibold text-ink">Buscar</label>
        <div class="pointer-events-none absolute bottom-0 left-0 flex h-control items-center pl-4">
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <input
            type="text"
            id="treatments-search"
            placeholder="Nombre o descripción"
            class="block h-control w-full rounded-control border border-field bg-surface pl-11 pr-4 text-control text-ink"
        >
    </div>

    <article class="hidden rounded-box border border-secondary bg-primary-soft p-4 md:block md:p-5">
        <div class="flex items-start gap-4">
            <div data-accent class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
                    <path d="M9 13h6" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Gestión de tratamientos</p>
                <p class="mt-1 text-sm leading-6 text-ink">Desde aquí puedes crear, consultar, editar y eliminar los tratamientos que se ofrecen al agendar una cita.</p>
            </div>
        </div>
    </article>

    <div id="treatments-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger"></div>

    <div id="treatments-loading" class="hidden rounded-card border border-line bg-surface p-5 text-center text-sm text-muted shadow-sm">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-hidden="true"></div>
        <p class="mt-2">Cargando tratamientos...</p>
    </div>

    <div id="treatments-list" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"></div>

    <div id="treatments-empty" class="hidden rounded-card border border-line bg-surface p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-canvas text-muted" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
            </svg>
        </div>
        <h2 class="mt-4 text-sm font-semibold text-ink">No se encontraron tratamientos</h2>
        <p class="mt-1 text-sm text-muted">Ajusta tu búsqueda o crea un tratamiento nuevo.</p>
    </div>

    <x-ui.dialog id="treatments-view-modal" title="Detalle del tratamiento">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-sm font-semibold text-muted">Nombre</dt>
                <dd class="mt-1 wrap-break-word text-base font-semibold text-ink" data-treatments-view-name>-</dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-semibold text-muted">Descripción</dt>
                <dd class="mt-1 whitespace-pre-line wrap-break-word text-sm leading-6 text-ink" data-treatments-view-description>-</dd>
            </div>

            <div>
                <dt class="text-sm font-semibold text-muted">Duración</dt>
                <dd class="mt-1 text-base font-semibold text-ink" data-treatments-view-time>-</dd>
            </div>
        </dl>

        <x-slot:footer>
            <x-ui.button data-treatments-view-close>Cerrar</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>

    <x-ui.dialog id="treatments-create-modal" title="Crear tratamiento">
        <form id="treatments-create-form" data-treatments-create-form class="flex flex-col gap-4">
            <x-ui.input
                variant="string"
                id="treatments-create-name"
                name="name"
                label="Nombre del tratamiento"
                placeholder="Ej. Limpieza profunda"
                autocomplete="off"
                data-treatments-create-name
                required
            />

            <div>
                <label for="treatments-create-description" class="mb-1.5 block text-sm font-semibold text-ink">
                    Descripción
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <textarea
                    id="treatments-create-description"
                    name="description"
                    rows="4"
                    data-treatments-create-description
                    class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink"
                    placeholder="Describe el tratamiento"
                    required
                ></textarea>
            </div>

            <x-ui.input
                variant="number"
                id="treatments-create-time"
                name="time"
                label="Duración en minutos"
                placeholder="60"
                hint="De 0 a 240 minutos."
                autocomplete="off"
                data-treatments-create-time
                required
            />
        </form>

        <x-slot:footer>
            <x-ui.button data-treatments-create-close>Cancelar</x-ui.button>
            <x-ui.button variant="primary" type="submit" form="treatments-create-form" data-treatments-create-submit>Guardar tratamiento</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>

    <x-ui.dialog id="treatments-edit-modal" title="Editar tratamiento">
        <form id="treatments-edit-form" data-treatments-edit-form class="flex flex-col gap-4">
            <input type="hidden" data-treatments-edit-id name="id" />

            <x-ui.input
                variant="string"
                id="treatments-edit-name"
                name="name"
                label="Nombre del tratamiento"
                autocomplete="off"
                data-treatments-edit-name
                required
            />

            <div>
                <label for="treatments-edit-description" class="mb-1.5 block text-sm font-semibold text-ink">
                    Descripción
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <textarea
                    id="treatments-edit-description"
                    name="description"
                    rows="4"
                    data-treatments-edit-description
                    class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink"
                    required
                ></textarea>
            </div>

            <x-ui.input
                variant="number"
                id="treatments-edit-time"
                name="time"
                label="Duración en minutos"
                hint="De 0 a 240 minutos."
                autocomplete="off"
                data-treatments-edit-time
                required
            />
        </form>

        <x-slot:footer>
            <x-ui.button data-treatments-edit-close>Cancelar</x-ui.button>
            <x-ui.button variant="primary" type="submit" form="treatments-edit-form" data-treatments-edit-submit>Guardar cambios</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>

    <x-ui.dialog id="treatments-delete-modal" title="Eliminar tratamiento" size="sm">
        <form id="treatments-delete-form" data-treatments-delete-form>
            <input type="hidden" data-treatments-delete-id name="id" />

            <p class="text-sm leading-6 text-ink">
                Vas a eliminar el tratamiento
                <span data-treatments-delete-target class="font-semibold">seleccionado</span>.
                Esta acción no se puede deshacer.
            </p>
        </form>

        <x-slot:footer>
            <x-ui.button data-treatments-delete-close>Cancelar</x-ui.button>
            <x-ui.button variant="danger" type="submit" form="treatments-delete-form" data-treatments-delete-submit>Eliminar</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>
</div>

@vite('resources/js/pages/tratamientos/index.js')
@endsection
