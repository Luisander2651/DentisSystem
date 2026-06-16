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

    function renderCertificationCards(records) {
        var visibleCount = 0;
        var hiddenCount = 0;

        records.forEach(function (record) {
            if (normalizeCertificationStatus(record.status) === 'visible') {
                visibleCount += 1;
                return;
            }

            hiddenCount += 1;
        });

        if (certificationsCount) {
            certificationsCount.textContent = String(records.length);
        }

        if (certificationsVisibleCount) {
            certificationsVisibleCount.textContent = String(visibleCount);
        }

        if (certificationsHiddenCount) {
            certificationsHiddenCount.textContent = String(hiddenCount);
        }

        certificationsList.innerHTML = records.map(function (record) {
            var id = record.id ?? '';
            var name = record.name ?? '';
            var description = record.description ?? '';
            var date = formatDate(record.date);
            var rawDate = record.date ? String(record.date).split(' ')[0] : '';
            var imageUrl = record.image_url ?? '';
            var status = normalizeCertificationStatus(record.status);
            var statusLabel = status === 'visible' ? 'Visible' : 'Oculto';
            var statusClasses = status === 'visible'
                ? 'bg-[#FDF1F6] text-[#B5114A]'
                : 'bg-red-50 text-red-700';

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex-1">',
                            '<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Certificacion</p>',
                            '<h3 class="mt-2 text-base font-semibold text-slate-900 break-words">', escapeHtml(name), '</h3>',
                        '</div>',
                        '<span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold ', statusClasses, '">', statusLabel, '</span>',
                    '</div>',
                    
                    '<div class="mt-4 rounded-2xl border border-dashed border-[#F5C2D6] bg-linear-to-br from-[#FFFDF6] to-white p-4">',
                        '<div class="flex min-h-40 items-center justify-center overflow-hidden rounded-2xl border border-white/80 bg-white/70">',
                            imageUrl 
                                ? '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(name) + '" class="h-full w-full object-cover" loading="lazy" />'
                                : '<div class="text-center p-4"><p class="text-xs text-slate-500">Sin imagen</p></div>',
                        '</div>',
                    '</div>',

                    '<p class="mt-3 text-sm leading-6 text-slate-600 break-words line-clamp-2">', escapeHtml(description), '</p>',
                    
                    '<div class="mt-4 grid gap-2">',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">',
                            '<span class="font-semibold uppercase tracking-wider text-slate-400">Fecha:</span>',
                            '<span>', escapeHtml(date), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex flex-wrap gap-2">',
                        '<button type="button" data-certifications-edit data-certification-id="', escapeHtml(id), '" data-certification-name="', escapeHtml(name), '" data-certification-description="', escapeHtml(description), '" data-certification-date="', escapeHtml(rawDate), '" data-certification-status="', escapeHtml(status), '" data-certification-image="', escapeHtml(imageUrl), '" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Editar</button>',
                        '<button type="button" data-certifications-delete data-certification-id="', escapeHtml(id), '" data-certification-name="', escapeHtml(name), '" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Eliminar</button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');

        if (certificationsEmpty) {
            certificationsEmpty.classList.toggle('hidden', records.length !== 0);
        }
    }

    async function loadCertifications() {
        setCertificationsLoading(true);
        hideCertificationsError();

        try {
            var response = await fetch('/api/v1/certifications', {
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
                showCertificationsError(payload.error || payload.message || 'No se pudieron cargar las certificaciones.');
                renderCertificationCards([]);
                return;
            }

            renderCertificationCards(normalizeCertificationPayload(payload));
        } catch (error) {
            showCertificationsError('Error de conexion. Intentalo de nuevo.');
            renderCertificationCards([]);
        } finally {
            setCertificationsLoading(false);
        }
    }

    var certificationsApi = {
        reload: loadCertifications,
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