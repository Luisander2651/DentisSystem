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
            var statusLabel = status === 'visible' ? 'Visible' : 'Oculto';
            var statusClasses = status === 'visible'
                ? 'bg-[#FDF1F6] text-[#B5114A]'
                : 'bg-red-50 text-red-700';

            var initial = author.charAt(0).toUpperCase();

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex items-center gap-3">',
                            '<div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#FDF1F6] text-sm font-semibold text-[#B5114A]">', escapeHtml(initial), '</div>',
                            '<div>',
                                '<h3 class="text-base font-semibold text-slate-900 break-words">', escapeHtml(author), '</h3>',
                                '<p class="text-xs text-slate-500">Paciente verificado</p>',
                            '</div>',
                        '</div>',
                        '<span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold ', statusClasses, '">', statusLabel, '</span>',
                    '</div>',
                    
                    '<p class="mt-4 text-sm leading-6 text-slate-600 break-words line-clamp-4">', escapeHtml(description), '</p>',
                    
                    '<div class="mt-4 grid gap-2">',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">',
                            '<span class="font-semibold uppercase tracking-wider text-slate-400">Recibido:</span>',
                            '<span>', escapeHtml(createdAt), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex flex-wrap gap-2">',
                        '<button type="button" data-testimonials-edit data-testimonial-id="', escapeHtml(id), '" data-testimonial-author="', escapeHtml(author), '" data-testimonial-description="', escapeHtml(description), '" data-testimonial-status="', escapeHtml(status), '" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Editar</button>',
                        '<button type="button" data-testimonials-delete data-testimonial-id="', escapeHtml(id), '" data-testimonial-author="', escapeHtml(author), '" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Eliminar</button>',
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
                showTestimonialsError(payload.error || payload.message || 'No se pudieron cargar los testimonios.');
                renderTestimonialCards([]);
                return;
            }

            renderTestimonialCards(normalizeTestimonialPayload(payload));
        } catch (error) {
            showTestimonialsError('Error de conexion. Intentalo de nuevo.');
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