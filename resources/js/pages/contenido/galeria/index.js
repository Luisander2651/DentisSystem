import { initGalleryEditModal } from './edit-modal';
import { initGalleryDeleteModal } from './delete-modal';
import { initGalleryCreateModal } from './create-modal';

export function initGalleryModule() {
    var galleryList = document.querySelector('[data-gallery-list]');
    var galleryError = document.querySelector('[data-gallery-error]');
    var galleryLoading = document.querySelector('[data-gallery-loading]');
    var galleryEmpty = document.querySelector('[data-gallery-empty]');
    var galleryCount = document.querySelector('[data-gallery-count]');
    var galleryVisibleCount = document.querySelector('[data-gallery-visible-count]');
    var galleryHiddenCount = document.querySelector('[data-gallery-hidden-count]');

    if (!galleryList) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) {
            return 'Sin fecha';
        }

        var date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat('es-MX', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function getImageLabel(url) {
        if (!url) {
            return 'Sin imagen';
        }

        try {
            var parsed = new URL(url, window.location.origin);
            return parsed.pathname.split('/').filter(Boolean).pop() || parsed.hostname;
        } catch (error) {
            return String(url).split('/').filter(Boolean).pop() || String(url);
        }
    }

    function normalizeGalleryPayload(payload) {
        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        if (payload && typeof payload === 'object' && ('url' in payload || 'description' in payload)) {
            return [payload];
        }

        return Array.isArray(payload) ? payload : [];
    }

    function normalizeGalleryStatus(value) {
        var normalized = String(value ?? '').trim().toLowerCase();

        if (normalized === 'visible' || normalized === 'visible_en_web' || normalized === 'publicado' || normalized === 'active' || normalized === 'activo') {
            return 'visible';
        }

        if (normalized === 'hidden' || normalized === 'oculto' || normalized === 'invisible' || normalized === 'inactive' || normalized === 'inactivo') {
            return 'hidden';
        }

        return 'hidden';
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function setGalleryLoading(isLoading) {
        if (galleryLoading) {
            galleryLoading.classList.toggle('hidden', !isLoading);
        }
    }

    function showGalleryError(message) {
        if (!galleryError) {
            return;
        }

        galleryError.textContent = message;
        galleryError.classList.remove('hidden');
    }

    function hideGalleryError() {
        if (!galleryError) {
            return;
        }

        galleryError.textContent = '';
        galleryError.classList.add('hidden');
    }

    function renderGalleryCards(records) {
        var visibleCount = 0;
        var hiddenCount = 0;

        records.forEach(function (record) {
            if (normalizeGalleryStatus(record.status) === 'visible') {
                visibleCount += 1;
                return;
            }

            hiddenCount += 1;
        });

        if (galleryCount) {
            galleryCount.textContent = String(records.length);
        }

        if (galleryVisibleCount) {
            galleryVisibleCount.textContent = String(visibleCount);
        }

        if (galleryHiddenCount) {
            galleryHiddenCount.textContent = String(hiddenCount);
        }

        galleryList.innerHTML = records.map(function (record) {
            var url = record.url ?? '';
            var description = record.description ?? '';
            var createdAt = formatDate(record.created_at);
            var updatedAt = formatDate(record.updated_at);
            var imageLabel = getImageLabel(url);
            var status = normalizeGalleryStatus(record.status);
            var statusLabel = status === 'visible' ? 'Visible' : 'Oculto';
            var statusClasses = status === 'visible'
                ? 'bg-[#FDF1F6] text-[#B5114A]'
                : 'bg-red-50 text-red-700';

            var imageMarkup = url
                ? [
                    '<img src="', escapeHtml(url), '" alt="', escapeHtml(imageLabel), '" class="h-full w-full object-cover" loading="lazy" />',
                ].join('')
                : [
                    '<div class="text-center">',
                        '<div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-[#FDF1F6] text-[#E91E63] shadow-sm">',
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                                '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" />',
                                '<circle cx="8.5" cy="8.5" r="1.5" />',
                                '<path d="m21 15-5-5L5 21" />',
                            '</svg>',
                        '</div>',
                        '<p class="text-sm font-semibold text-slate-700">Sin imagen</p>',
                        '<p class="mt-1 text-xs text-slate-500">Este registro no trae url disponible.</p>',
                    '</div>',
                ].join('');

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div>',
                            '<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Galería</p>',
                            '<h3 class="mt-2 text-base font-semibold text-slate-900 break-words">', escapeHtml(imageLabel), '</h3>',
                        '</div>',
                        '<span class="rounded-full px-3 py-1 text-xs font-semibold ', statusClasses, '">', statusLabel, '</span>',
                    '</div>',
                    '<div class="mt-4 overflow-hidden rounded-2xl border border-dashed border-[#F5C2D6] bg-linear-to-br from-[#FFF7FA] to-white p-4">',
                        '<div class="flex min-h-56 items-center justify-center overflow-hidden rounded-2xl border border-white/80 bg-white/70 text-center">',
                            '<div class="relative h-full min-h-56 w-full">',
                                imageMarkup,
                                '<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-4 text-left text-white">',
                                    '<p class="text-sm font-semibold break-words">', escapeHtml(imageLabel), '</p>',
                                    '<p class="mt-1 text-xs text-white/80">Imagen cargada desde la base de datos.</p>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>',
                    '<p class="mt-3 text-sm leading-6 text-slate-600 break-words">', escapeHtml(description), '</p>',
                    '<div class="mt-4 grid gap-2">',
                        '<div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 break-all">', escapeHtml(url || 'Sin URL'), '</div>',
                        '<div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 break-words">Creado: ', escapeHtml(createdAt), '</div>',
                        '<div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 break-words">Actualizado: ', escapeHtml(updatedAt), '</div>',
                    '</div>',
                    '<div class="mt-4 flex flex-wrap gap-2">',
                        '<button type="button" data-gallery-edit data-gallery-id="', escapeHtml(record.id), '" data-gallery-description="', escapeHtml(description), '" data-gallery-url="', escapeHtml(url), '" data-gallery-status="', escapeHtml(status), '" data-gallery-label="', escapeHtml(imageLabel), '" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Editar</button>',
                        '<button type="button" data-gallery-delete data-gallery-id="', escapeHtml(record.id), '" data-gallery-label="', escapeHtml(imageLabel), '" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Eliminar</button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');

        if (galleryEmpty) {
            galleryEmpty.classList.toggle('hidden', records.length !== 0);
        }
    }

    async function loadGalleryImages() {
        setGalleryLoading(true);
        hideGalleryError();

        try {
            var response = await fetch('/api/v1/gallery-images', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            var payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                showGalleryError(payload.error || payload.message || 'No se pudieron cargar los registros de galeria.');
                renderGalleryCards([]);
                return;
            }

            renderGalleryCards(normalizeGalleryPayload(payload));
        } catch (error) {
            showGalleryError('Error de conexion. Intentalo de nuevo.');
            renderGalleryCards([]);
        } finally {
            setGalleryLoading(false);
        }
    }

    var galleryApi = {
        reload: loadGalleryImages,
        showError: showGalleryError,
        hideError: hideGalleryError,
        getCookie: getCookie,
    };

    initGalleryEditModal({
        root: document,
        api: galleryApi,
    });

    initGalleryDeleteModal({
        root: document,
        api: galleryApi,
    });

    initGalleryCreateModal({
        root: document,
        api: galleryApi,
    });

    loadGalleryImages();
}