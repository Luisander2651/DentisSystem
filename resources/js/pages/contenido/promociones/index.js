import { initPromotionsEditModal } from './edit-modal';
import { initPromotionsDeleteModal } from './delete-modal';
import { initPromotionsCreateModal } from './create-modal';

export function initPromocionesModule() {
    var promotionsList = document.querySelector('[data-promotions-list]');
    var promotionsError = document.querySelector('[data-promotions-error]');
    var promotionsLoading = document.querySelector('[data-promotions-loading]');
    var promotionsEmpty = document.querySelector('[data-promotions-empty]');
    var promotionsCount = document.querySelector('[data-promotions-count]');
    var promotionsVisibleCount = document.querySelector('[data-promotions-visible-count]');
    var promotionsHiddenCount = document.querySelector('[data-promotions-hidden-count]');

    if (!promotionsList) {
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
        }).format(date);
    }

    function normalizePromotionPayload(payload) {
        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        if (payload && typeof payload === 'object' && ('name' in payload || 'discount_percentage' in payload)) {
            return [payload];
        }

        return Array.isArray(payload) ? payload : [];
    }

    function normalizePromotionStatus(value) {
        var normalized = String(value ?? '').trim().toLowerCase();

        if (normalized === 'visible' || normalized === 'active' || normalized === 'activo' || normalized === 'publicado') {
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

    function setPromotionsLoading(isLoading) {
        if (promotionsLoading) {
            promotionsLoading.classList.toggle('hidden', !isLoading);
        }
    }

    function showPromotionsError(message) {
        if (!promotionsError) {
            return;
        }

        promotionsError.textContent = message;
        promotionsError.classList.remove('hidden');
    }

    function hidePromotionsError() {
        if (!promotionsError) {
            return;
        }

        promotionsError.textContent = '';
        promotionsError.classList.add('hidden');
    }

    function renderPromotionCards(records) {
        var visibleCount = 0;
        var hiddenCount = 0;

        records.forEach(function (record) {
            if (normalizePromotionStatus(record.status) === 'visible') {
                visibleCount += 1;
                return;
            }

            hiddenCount += 1;
        });

        if (promotionsCount) {
            promotionsCount.textContent = String(records.length);
        }

        if (promotionsVisibleCount) {
            promotionsVisibleCount.textContent = String(visibleCount);
        }

        if (promotionsHiddenCount) {
            promotionsHiddenCount.textContent = String(hiddenCount);
        }

        promotionsList.innerHTML = records.map(function (record) {
            var id = record.id ?? '';
            var name = record.name ?? '';
            var description = record.description ?? '';
            var discount = record.discount_percentage ?? '0';
            var rawStartDate = record.start_date ? String(record.start_date).split(' ')[0] : '';
            var rawEndDate = record.end_date ? String(record.end_date).split(' ')[0] : '';
            var startDate = formatDate(record.start_date);
            var endDate = formatDate(record.end_date);
            var status = normalizePromotionStatus(record.status);
            var statusLabel = status === 'visible' ? 'Visible' : 'Oculto';
            var statusClasses = status === 'visible'
                ? 'bg-[#FDF1F6] text-[#B5114A]'
                : 'bg-red-50 text-red-700';

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex-1">',
                            '<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Promocion</p>',
                            '<h3 class="mt-2 text-base font-semibold text-slate-900 break-words">', escapeHtml(name), '</h3>',
                        '</div>',
                        '<span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold ', statusClasses, '">', statusLabel, '</span>',
                    '</div>',
                    
                    '<div class="mt-4 rounded-2xl border border-dashed border-[#F5C2D6] bg-linear-to-br from-[#FFFDF6] to-white p-4">',
                        '<div class="flex min-h-32 items-center justify-center rounded-2xl border border-white/80 bg-white/70 text-center">',
                            '<div>',
                                '<div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-white text-amber-500 shadow-sm">',
                                    '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                                        '<line x1="19" y1="5" x2="5" y2="19" />',
                                        '<circle cx="6.5" cy="6.5" r="2.5" />',
                                        '<circle cx="17.5" cy="17.5" r="2.5" />',
                                    '</svg>',
                                '</div>',
                                '<p class="text-xs font-bold text-amber-600">', escapeHtml(discount), '% de descuento</p>',
                            '</div>',
                        '</div>',
                    '</div>',

                    '<p class="mt-3 text-sm leading-6 text-slate-600 break-words line-clamp-2">', escapeHtml(description), '</p>',
                    
                    '<div class="mt-4 grid gap-2">',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">',
                            '<span class="font-semibold uppercase tracking-wider text-slate-400">Inicio:</span>',
                            '<span>', escapeHtml(startDate), '</span>',
                        '</div>',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">',
                            '<span class="font-semibold uppercase tracking-wider text-slate-400">Fin:</span>',
                            '<span>', escapeHtml(endDate), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex flex-wrap gap-2">',
                        '<button type="button" data-promotions-edit data-promotion-id="', escapeHtml(id), '" data-promotion-name="', escapeHtml(name), '" data-promotion-description="', escapeHtml(description), '" data-promotion-discount="', escapeHtml(discount), '" data-promotion-status="', escapeHtml(status), '" data-promotion-start-date="', escapeHtml(rawStartDate), '" data-promotion-end-date="', escapeHtml(rawEndDate), '" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Editar</button>',
                        '<button type="button" data-promotions-delete data-promotion-id="', escapeHtml(id), '" data-promotion-name="', escapeHtml(name), '" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Eliminar</button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');

        if (promotionsEmpty) {
            promotionsEmpty.classList.toggle('hidden', records.length !== 0);
        }
    }

    async function loadPromotions() {
        setPromotionsLoading(true);
        hidePromotionsError();

        try {
            var response = await fetch('/api/v1/promotions', {
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
                showPromotionsError(payload.error || payload.message || 'No se pudieron cargar las promociones.');
                renderPromotionCards([]);
                return;
            }

            renderPromotionCards(normalizePromotionPayload(payload));
        } catch (error) {
            showPromotionsError('Error de conexion. Intentalo de nuevo.');
            renderPromotionCards([]);
        } finally {
            setPromotionsLoading(false);
        }
    }

    var promotionsApi = {
        reload: loadPromotions,
        showError: showPromotionsError,
        hideError: hidePromotionsError,
        getCookie: getCookie,
    };

    initPromotionsEditModal({
        root: document,
        api: promotionsApi,
    });

    initPromotionsDeleteModal({
        root: document,
        api: promotionsApi,
    });

    initPromotionsCreateModal({
        root: document,
        api: promotionsApi,
    });

    loadPromotions();
}