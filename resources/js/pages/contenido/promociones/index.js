import { stateBadge } from '../../../ui/badge';
import { failureMessage } from '../../../ui/status';
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

            return [
                '<article class="flex flex-col rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    '<div class="flex items-start justify-between gap-3">',
                        '<h3 class="min-w-0 text-base font-semibold text-ink wrap-anywhere">', escapeHtml(name), '</h3>',
                        stateBadge(status),
                    '</div>',
                    '<p class="mt-2 text-xl font-bold text-ink">', escapeHtml(discount), ' % <span class="text-sm font-semibold text-muted">de descuento</span></p>',
                    '<p class="mt-2 line-clamp-2 text-sm leading-6 text-muted wrap-anywhere">', escapeHtml(description), '</p>',
                    '<p class="mt-3 text-sm text-ink">Del ', escapeHtml(startDate), ' al ', escapeHtml(endDate), '</p>',
                    '<div class="mt-4 flex gap-2">',
                        '<button type="button" data-promotions-edit data-promotion-id="', escapeHtml(id), '" data-promotion-name="', escapeHtml(name), '" data-promotion-description="', escapeHtml(description), '" data-promotion-discount="', escapeHtml(discount), '" data-promotion-status="', escapeHtml(status), '" data-promotion-start-date="', escapeHtml(rawStartDate), '" data-promotion-end-date="', escapeHtml(rawEndDate), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar<span class="sr-only"> ', escapeHtml(name), '</span></button>',
                        '<button type="button" data-promotions-delete data-promotion-id="', escapeHtml(id), '" data-promotion-name="', escapeHtml(name), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Eliminar<span class="sr-only"> ', escapeHtml(name), '</span></button>',
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
                showPromotionsError(failureMessage(response.status, payload));
                renderPromotionCards([]);
                return;
            }

            renderPromotionCards(normalizePromotionPayload(payload));
        } catch (error) {
            showPromotionsError(failureMessage(0));
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