import { stateBadge } from '../../../ui/badge';
import { mountPagination } from '../../../ui/pagination';
import { failureMessage } from '../../../ui/status';
import { initCertificationsEditModal } from './edit-modal';
import { initCertificationsDeleteModal } from './delete-modal';
import { initCertificationsCreateModal } from './create-modal';

export function initCertificacionesModule() {
    var certificationsList = document.querySelector('[data-certifications-list]');
    var certificationsError = document.querySelector('[data-certifications-error]');
    var certificationsLoading = document.querySelector('[data-certifications-loading]');
    var certificationsEmpty = document.querySelector('[data-certifications-empty]');
    var certificationsCount = document.querySelector('[data-certifications-count]');
    var certificationsVisibleCount = document.querySelector('[data-certifications-visible-count]');
    var certificationsHiddenCount = document.querySelector('[data-certifications-hidden-count]');

    if (!certificationsList) {
        return;
    }

    // Spec 028 (CA1, CA5, CA10): the server answers one page with the counts of the whole list;
    // saving or deleting reloads the page shown.
    var currentPage = 1;
    var requestNumber = 0;
    var pagination = mountPagination(document.querySelector('[data-certifications-pagination]'), {
        noun: 'certificaciones',
        onChange: function (page) {
            currentPage = page;
            loadCertifications({ announcePage: true });
        },
        focusTarget: certificationsList.closest('[data-content-panel]')?.querySelector('h2') ?? null,
    });

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

    function normalizeCertificationPayload(payload) {
        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        if (payload && typeof payload === 'object' && ('name' in payload || 'image_url' in payload)) {
            return [payload];
        }

        return Array.isArray(payload) ? payload : [];
    }

    function normalizeCertificationStatus(value) {
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

    function setCertificationsLoading(isLoading) {
        if (certificationsLoading) {
            certificationsLoading.classList.toggle('hidden', !isLoading);
        }
    }

    function showCertificationsError(message) {
        if (!certificationsError) {
            return;
        }

        certificationsError.textContent = message;
        certificationsError.classList.remove('hidden');
    }

    function hideCertificationsError() {
        if (!certificationsError) {
            return;
        }

        certificationsError.textContent = '';
        certificationsError.classList.add('hidden');
    }

    function renderCertificationsCounts(counts) {
        if (!counts) {
            return;
        }

        if (certificationsCount) {
            certificationsCount.textContent = String(counts.total ?? 0);
        }

        if (certificationsVisibleCount) {
            certificationsVisibleCount.textContent = String(counts.visible ?? 0);
        }

        if (certificationsHiddenCount) {
            certificationsHiddenCount.textContent = String(counts.hidden ?? 0);
        }
    }

    function renderCertificationCards(records) {
        certificationsList.innerHTML = records.map(function (record) {
            var id = record.id ?? '';
            var name = record.name ?? '';
            var description = record.description ?? '';
            var date = formatDate(record.date);
            var rawDate = record.date ? String(record.date).split(' ')[0] : '';
            var imageUrl = record.image_url ?? '';
            var status = normalizeCertificationStatus(record.status);

            return [
                '<article class="flex flex-col rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    imageUrl
                        ? '<img src="' + escapeHtml(imageUrl) + '" alt="" class="h-40 w-full rounded-box border border-line bg-canvas object-contain" loading="lazy" />'
                        : '<div class="flex h-40 w-full items-center justify-center rounded-box border border-line bg-canvas text-sm font-semibold text-muted">Sin imagen</div>',
                    '<div class="mt-4 flex items-start justify-between gap-3">',
                        '<h3 class="min-w-0 text-base font-semibold text-ink wrap-anywhere">', escapeHtml(name), '</h3>',
                        stateBadge(status),
                    '</div>',
                    '<p class="mt-2 line-clamp-2 text-sm leading-6 text-muted wrap-anywhere">', escapeHtml(description), '</p>',
                    '<p class="mt-2 text-sm text-ink">Fecha: ', escapeHtml(date), '</p>',
                    '<div class="mt-4 flex gap-2">',
                        '<button type="button" data-certifications-edit data-certification-id="', escapeHtml(id), '" data-certification-name="', escapeHtml(name), '" data-certification-description="', escapeHtml(description), '" data-certification-date="', escapeHtml(rawDate), '" data-certification-status="', escapeHtml(status), '" data-certification-image="', escapeHtml(imageUrl), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar<span class="sr-only"> ', escapeHtml(name), '</span></button>',
                        '<button type="button" data-certifications-delete data-certification-id="', escapeHtml(id), '" data-certification-name="', escapeHtml(name), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Eliminar<span class="sr-only"> ', escapeHtml(name), '</span></button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');

        if (certificationsEmpty) {
            certificationsEmpty.classList.toggle('hidden', records.length !== 0);
        }
    }

    async function loadCertifications(options) {
        var thisRequest = ++requestNumber;

        setCertificationsLoading(true);
        hideCertificationsError();

        try {
            var response = await fetch('/api/v1/certifications?page=' + encodeURIComponent(String(currentPage)), {
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

            if (thisRequest !== requestNumber) {
                return;
            }

            if (!response.ok) {
                showCertificationsError(failureMessage(response.status, payload));
                renderCertificationCards([]);
                pagination.render(null);
                return;
            }

            var meta = payload && payload.meta ? payload.meta : null;

            currentPage = meta && meta.page ? meta.page : 1;
            renderCertificationsCounts(meta && meta.counts);
            renderCertificationCards(normalizeCertificationPayload(payload));
            pagination.render(meta);

            if (options && options.announcePage) {
                pagination.announcePage(meta);
            }
        } catch (error) {
            if (thisRequest !== requestNumber) {
                return;
            }

            showCertificationsError(failureMessage(0));
            renderCertificationCards([]);
            pagination.render(null);
        } finally {
            if (thisRequest === requestNumber) {
                setCertificationsLoading(false);
            }
        }
    }

    var certificationsApi = {
        reload: function () {
            return loadCertifications();
        },
        showError: showCertificationsError,
        hideError: hideCertificationsError,
        getCookie: getCookie,
    };

    initCertificationsEditModal({
        root: document,
        api: certificationsApi,
    });

    initCertificationsDeleteModal({
        root: document,
        api: certificationsApi,
    });

    initCertificationsCreateModal({
        root: document,
        api: certificationsApi,
    });

    loadCertifications();
}