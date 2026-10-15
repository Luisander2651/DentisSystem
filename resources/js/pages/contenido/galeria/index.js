import { stateBadge } from '../../../ui/badge';
import { failureMessage } from '../../../ui/status';
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
            var updatedAt = formatDate(record.updated_at || record.created_at);
            var title = String(description).trim() || 'Imagen sin descripción';
            var status = normalizeGalleryStatus(record.status);
            var imageMarkup = url
                ? '<img src="' + escapeHtml(url) + '" alt="" class="h-48 w-full rounded-box border border-line object-cover" loading="lazy" />'
                : '<div class="flex h-48 w-full items-center justify-center rounded-box border border-line bg-canvas text-sm font-semibold text-muted">Sin imagen</div>';

            return [
                '<article class="flex flex-col rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    imageMarkup,
                    '<div class="mt-4 flex items-start justify-between gap-3">',
                        '<h3 class="min-w-0 text-base font-semibold text-ink wrap-anywhere">', escapeHtml(title), '</h3>',
                        stateBadge(status),
                    '</div>',
                    '<p class="mt-1 text-sm text-muted">Actualizada: ', escapeHtml(updatedAt), '</p>',
                    '<div class="mt-4 flex gap-2">',
                        '<button type="button" data-gallery-edit data-gallery-id="', escapeHtml(record.id), '" data-gallery-description="', escapeHtml(description), '" data-gallery-url="', escapeHtml(url), '" data-gallery-status="', escapeHtml(status), '" data-gallery-label="', escapeHtml(title), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar<span class="sr-only"> ', escapeHtml(title), '</span></button>',
                        '<button type="button" data-gallery-delete data-gallery-id="', escapeHtml(record.id), '" data-gallery-label="', escapeHtml(title), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Eliminar<span class="sr-only"> ', escapeHtml(title), '</span></button>',
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
                showGalleryError(failureMessage(response.status, payload));
                renderGalleryCards([]);
                return;
            }

            renderGalleryCards(normalizeGalleryPayload(payload));
        } catch (error) {
            showGalleryError(failureMessage(0));
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