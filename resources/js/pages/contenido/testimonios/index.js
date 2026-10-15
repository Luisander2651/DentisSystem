import { stateBadge } from '../../../ui/badge';
import { failureMessage } from '../../../ui/status';
import { initTestimonialsEditModal } from './edit-modal';
import { initTestimonialsDeleteModal } from './delete-modal';

export function initTestimoniosModule() {
    var testimonialsList = document.querySelector('[data-testimonials-list]');
    var testimonialsError = document.querySelector('[data-testimonials-error]');
    var testimonialsLoading = document.querySelector('[data-testimonials-loading]');
    var testimonialsEmpty = document.querySelector('[data-testimonials-empty]');
    var testimonialsCount = document.querySelector('[data-testimonials-count]');
    var testimonialsVisibleCount = document.querySelector('[data-testimonials-visible-count]');
    var testimonialsHiddenCount = document.querySelector('[data-testimonials-hidden-count]');

    if (!testimonialsList) {
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

    function normalizeTestimonialPayload(payload) {
        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        if (payload && typeof payload === 'object' && ('author' in payload || 'description' in payload)) {
            return [payload];
        }

        return Array.isArray(payload) ? payload : [];
    }

    function normalizeTestimonialStatus(value) {
        var normalized = String(value ?? '').trim().toLowerCase();

        if (normalized === 'visible' || normalized === 'active' || normalized === 'activo' || normalized === 'publicado' || normalized === 'published') {
            return 'visible';
        }

        return 'oculto';
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function setTestimonialsLoading(isLoading) {
        if (testimonialsLoading) {
            testimonialsLoading.classList.toggle('hidden', !isLoading);
        }
    }

    function showTestimonialsError(message) {
        if (!testimonialsError) {
            return;
        }

        testimonialsError.textContent = message;
        testimonialsError.classList.remove('hidden');
    }

    function hideTestimonialsError() {
        if (!testimonialsError) {
            return;
        }

        testimonialsError.textContent = '';
        testimonialsError.classList.add('hidden');
    }

    function renderTestimonialCards(records) {
        var visibleCount = 0;
        var hiddenCount = 0;

        records.forEach(function (record) {
            if (normalizeTestimonialStatus(record.status) === 'visible') {
                visibleCount += 1;
                return;
            }

            hiddenCount += 1;
        });

        if (testimonialsCount) {
            testimonialsCount.textContent = String(records.length);
        }

        if (testimonialsVisibleCount) {
            testimonialsVisibleCount.textContent = String(visibleCount);
        }

        if (testimonialsHiddenCount) {
            testimonialsHiddenCount.textContent = String(hiddenCount);
        }

        testimonialsList.innerHTML = records.map(function (record) {
            var id = record.id ?? '';
            var author = record.author ?? '';
            var description = record.description ?? '';
            var createdAt = formatDate(record.created_at);
            var status = normalizeTestimonialStatus(record.status);

            return [
                '<article class="flex flex-col rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    '<div class="flex items-start justify-between gap-3">',
                        '<h3 class="min-w-0 text-base font-semibold text-ink wrap-anywhere">', escapeHtml(author), '</h3>',
                        stateBadge(status),
                    '</div>',
                    '<p class="mt-2 line-clamp-4 text-sm leading-6 text-ink wrap-anywhere">', escapeHtml(description), '</p>',
                    '<p class="mt-2 text-sm text-muted">Recibido: ', escapeHtml(createdAt), '</p>',
                    '<div class="mt-4 flex gap-2">',
                        '<button type="button" data-testimonials-edit data-testimonial-id="', escapeHtml(id), '" data-testimonial-author="', escapeHtml(author), '" data-testimonial-description="', escapeHtml(description), '" data-testimonial-status="', escapeHtml(status), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar<span class="sr-only"> el testimonio de ', escapeHtml(author), '</span></button>',
                        '<button type="button" data-testimonials-delete data-testimonial-id="', escapeHtml(id), '" data-testimonial-author="', escapeHtml(author), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Eliminar<span class="sr-only"> el testimonio de ', escapeHtml(author), '</span></button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');

        if (testimonialsEmpty) {
            testimonialsEmpty.classList.toggle('hidden', records.length !== 0);
        }
    }

    async function loadTestimonials() {
        setTestimonialsLoading(true);
        hideTestimonialsError();

        try {
            var response = await fetch('/api/v1/testimonials', {
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
                showTestimonialsError(failureMessage(response.status, payload));
                renderTestimonialCards([]);
                return;
            }

            renderTestimonialCards(normalizeTestimonialPayload(payload));
        } catch (error) {
            showTestimonialsError(failureMessage(0));
            renderTestimonialCards([]);
        } finally {
            setTestimonialsLoading(false);
        }
    }

    var testimonialsApi = {
        reload: loadTestimonials,
        showError: showTestimonialsError,
        hideError: hideTestimonialsError,
        getCookie: getCookie,
    };

    initTestimonialsEditModal({
        root: document,
        api: testimonialsApi,
    });

    initTestimonialsDeleteModal({
        root: document,
        api: testimonialsApi,
    });

    loadTestimonials();
}