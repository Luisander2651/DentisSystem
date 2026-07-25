@extends('layouts.admin')

@section('title', 'Tratamientos - Dentissa')

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-[#F5C2D6] bg-white p-6 shadow-sm shadow-[#FDF1F6] lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-[#FDF1F6] px-4 py-2 text-sm font-semibold text-[#B5114A]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
                    </svg>
                    Panel de seguridad para administradores
                </div>

                <div>
                    <x-ui.h1 as="h2" class="text-3xl! text-slate-900">Tratamientos</x-ui.h1>
                    <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                        Administra el catálogo de tratamientos disponibles. Desde aquí puedes crear, consultar, editar y eliminar tratamientos directamente contra la API admin.
                    </p>
                </div>
            </div>

            <button type="button" data-treatments-create-open class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#E91E63] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d61b5b]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                Nuevo tratamiento
            </button>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total Tratamientos</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-treatments-total-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Con duracion</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-treatments-with-time-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Promedio</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-treatments-average-time>0 min</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </div>
            <input
                type="text"
                id="treatments-search"
                placeholder="Buscar por nombre o descripcion..."
                class="block w-full rounded-2xl border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm placeholder:text-slate-400 shadow-sm transition-all focus:border-[#F5C2D6] focus:ring-4 focus:ring-[#F5C2D6]/10"
            >
        </div>
    </div>

    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
                    <path d="M9 13h6" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-[#B5114A]">Gestion de tratamientos</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Usa esta pantalla para administrar el catalogo de tratamientos con las rutas admin de la API.</p>
            </div>
        </div>
    </article>

    <div id="treatments-error" class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <div id="treatments-loading" class="hidden rounded-3xl border border-slate-200 bg-white p-5 text-center text-sm text-slate-600 shadow-sm">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-[#B5114A] border-t-transparent"></div>
        <p class="mt-2">Cargando tratamientos...</p>
    </div>

    <div id="treatments-list" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"></div>

    <div id="treatments-empty" class="hidden rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M8.5 3.5a4 4 0 0 0-4 4c0 1.2.5 2.3 1.3 3.2L12 17l6.2-6.2c.8-.9 1.3-2 1.3-3.2a4 4 0 0 0-4-4c-1.2 0-2.3.5-3.2 1.3L12 6.2l-.3-.4A4.5 4.5 0 0 0 8.5 3.5Z" />
            </svg>
        </div>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">No se encontraron tratamientos</h3>
        <p class="mt-1 text-sm text-slate-500">Intenta ajustar tu busqueda o crea un nuevo tratamiento.</p>
    </div>

    <div id="treatments-view-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" data-treatments-view-close></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-2xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tratamientos</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Detalle del tratamiento</h3>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nombre</p>
                                <p class="mt-2 text-base font-semibold text-slate-900" data-treatments-view-name>-</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Descripcion</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700" data-treatments-view-description>-</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Duracion</p>
                                <p class="mt-2 text-base font-semibold text-slate-900" data-treatments-view-time>-</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Id</p>
                                <p class="mt-2 text-base font-semibold text-slate-900" data-treatments-view-id>-</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                            <button type="button" data-treatments-view-close class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="treatments-create-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" data-treatments-create-close></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-2xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tratamientos</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Crear tratamiento</h3>
                    </div>

                    <form data-treatments-create-form class="space-y-4 px-6 py-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                Nombre del tratamiento
                                <input type="text" name="name" data-treatments-create-name class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" placeholder="Ej. Limpieza profunda" required />
                            </label>

                            <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                Descripcion
                                <textarea name="description" rows="4" data-treatments-create-description class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" placeholder="Describe el tratamiento" required></textarea>
                            </label>

                            <label class="block text-sm font-medium text-slate-700">
                                Duracion en minutos
                                <input type="number" name="time" min="0" max="240" step="1" data-treatments-create-time class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" placeholder="60" required />
                            </label>
                        </div>

                        <p data-treatments-create-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" data-treatments-create-close class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                            <button type="submit" data-treatments-create-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar tratamiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="treatments-edit-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" data-treatments-edit-close></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-2xl rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tratamientos</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Editar tratamiento</h3>
                    </div>

                    <form data-treatments-edit-form class="space-y-4 px-6 py-5">
                        <input type="hidden" data-treatments-edit-id name="id" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                Nombre del tratamiento
                                <input type="text" name="name" data-treatments-edit-name class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                            </label>

                            <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                Descripcion
                                <textarea name="description" rows="4" data-treatments-edit-description class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required></textarea>
                            </label>

                            <label class="block text-sm font-medium text-slate-700">
                                Duracion en minutos
                                <input type="number" name="time" min="0" max="240" step="1" data-treatments-edit-time class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#E91E63] focus:outline-none focus:ring-2 focus:ring-[#F8BBD0]" required />
                            </label>
                        </div>

                        <p data-treatments-edit-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" data-treatments-edit-close class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                            <button type="submit" data-treatments-edit-submit class="rounded-xl bg-[#E91E63] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#d81b60] disabled:cursor-not-allowed disabled:opacity-60">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="treatments-delete-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" data-treatments-delete-close></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-md rounded-[1.75rem] border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tratamientos</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Eliminar tratamiento</h3>
                    </div>

                    <form data-treatments-delete-form class="space-y-4 px-6 py-5">
                        <input type="hidden" data-treatments-delete-id name="id" />

                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                            <p class="text-sm leading-6 text-red-800">
                                Esta accion eliminara el tratamiento
                                <span data-treatments-delete-target class="font-semibold text-red-900">seleccionado</span>
                                de forma permanente.
                            </p>
                        </div>

                        <p data-treatments-delete-error class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" data-treatments-delete-close class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                            <button type="submit" data-treatments-delete-submit class="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@vite('resources/js/pages/tratamientos/index.js')
@endsection