function initFaqAccordion() {
    var items = document.querySelectorAll('.faq-item');
    items.forEach(function (item) {
        var trigger = item.querySelector('.faq-trigger');
        var content = item.querySelector('.faq-content');
        var icon = item.querySelector('.faq-icon');

        if (trigger && content) {
            trigger.addEventListener('click', function () {
                var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

                // Close all other accordions for premium feel
                items.forEach(function (otherItem) {
                    var otherContent = otherItem.querySelector('.faq-content');
                    var otherIcon = otherItem.querySelector('.faq-icon');
                    if (otherContent && otherContent !== content) {
                        otherContent.style.maxHeight = '0px';
                    }
                    if (otherIcon && otherIcon !== icon) {
                        otherIcon.textContent = '+';
                        otherIcon.style.transform = 'rotate(0deg)';
                    }
                });

                if (isOpen) {
                    content.style.maxHeight = '0px';
                    icon.textContent = '+';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    icon.textContent = '−';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        }
    });
}

function formatDate(value) {
    if (!value) return 'Sin fecha';
    var date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);

    return new Intl.DateTimeFormat('es-MX', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    }).format(date);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadPromotions() {
    var list = document.querySelector('[data-promotions-list]');
    var loading = document.querySelector('[data-promotions-loading]');
    var error = document.querySelector('[data-promotions-error]');
    var empty = document.querySelector('[data-promotions-empty]');

    if (!list) return;

    try {
        var response = await fetch('/api/v1/public/promotions');
        var records = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!response.ok) {
            if (error) {
                error.textContent = 'Ocurrió un error al cargar las promociones.';
                error.classList.remove('hidden');
            }
            return;
        }

        if (records.length === 0) {
            if (empty) empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = records.map(function (record) {
            var name = record.name ?? '';
            var description = record.description ?? '';
            var discount = record.discount_percentage ?? '0';
            var startDate = formatDate(record.start_date);
            var endDate = formatDate(record.end_date);

            return [
                '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs hover:shadow-md transition-all duration-300">',
                '<div class="flex items-start justify-between gap-4">',
                '<div class="flex-1">',
                '<span class="inline-flex items-center gap-1 rounded-full bg-pink-50 px-3 py-1 text-xs font-semibold text-[#B5114A]">',
                '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                '<path d="M20 7H4v4h16V7z" />',
                '<path d="M6 11v8h12v-8" />',
                '<path d="M12 7v12" />',
                '</svg>',
                '<span>Promoción</span>',
                '</span>',
                '<h3 class="mt-3 text-lg font-bold text-slate-900 break-words">', escapeHtml(name), '</h3>',
                '</div>',
                '<span class="shrink-0 rounded-2xl bg-amber-50 px-3 py-2 text-center shadow-xs border border-amber-100">',
                '<span class="block text-sm font-extrabold text-amber-600">', escapeHtml(discount), '%</span>',
                '<span class="block text-[9px] uppercase tracking-wider font-bold text-amber-500">Desc</span>',
                '</span>',
                '</div>',
                '<p class="mt-4 text-sm leading-6 text-slate-500 break-words line-clamp-3">', escapeHtml(description), '</p>',
                '<div class="mt-6 border-t border-slate-50 pt-4 flex flex-col gap-2">',
                '<div class="flex items-center justify-between text-xs text-slate-400">',
                '<span>Válido del:</span>',
                '<span class="font-semibold text-slate-600">', escapeHtml(startDate), '</span>',
                '</div>',
                '<div class="flex items-center justify-between text-xs text-slate-400">',
                '<span>Al:</span>',
                '<span class="font-semibold text-slate-600">', escapeHtml(endDate), '</span>',
                '</div>',
                '</div>',
                '</article>'
            ].join('');
        }).join('');

    } catch (err) {
        if (loading) loading.classList.add('hidden');
        if (error) {
            error.textContent = 'Error de conexión. Inténtalo de nuevo.';
            error.classList.remove('hidden');
        }
    }
}

function normalizeListPayload(payload) {
    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload)) {
        return payload;
    }

    return [];
}

function renderCertificationCard(record) {
    var name = record.name ?? '';
    var description = record.description ?? '';
    var imageUrl = record.image_url ?? '';
    var date = formatDate(record.date);

    return [
        '<article class="overflow-hidden rounded-3xl border border-[#F5C2D6] bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">',
        '<div class="aspect-[4/3] bg-gradient-to-br from-[#FFF7FA] to-white p-4">',
        '<div class="flex h-full items-center justify-center overflow-hidden rounded-2xl border border-dashed border-[#F5C2D6] bg-white/80">',
        imageUrl
            ? '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(name) + '" class="h-full w-full object-cover" loading="lazy" />'
            : '<div class="px-6 text-center"><span class="text-4xl">🏅</span><p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-[#B5114A]">Certificación</p></div>',
        '</div>',
        '</div>',
        '<div class="p-6">',
        '<span class="inline-flex items-center gap-1 rounded-full bg-[#FDF1F6] px-3 py-1 text-xs font-semibold text-[#B5114A]">',
        'Reconocimiento',
        '</span>',
        '<h3 class="mt-3 text-lg font-bold text-slate-900 break-words">', escapeHtml(name), '</h3>',
        '<p class="mt-3 text-sm leading-6 text-slate-500 break-words line-clamp-3">', escapeHtml(description), '</p>',
        '<div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-400">',
        '<span class="font-semibold uppercase tracking-wider text-[#B5114A]">Fecha</span>',
        '<span class="font-medium text-slate-500">', escapeHtml(date), '</span>',
        '</div>',
        '</div>',
        '</article>'
    ].join('');
}

async function loadCertifications() {
    var list = document.querySelector('[data-certifications-list]');
    var loading = document.querySelector('[data-certifications-loading]');
    var error = document.querySelector('[data-certifications-error]');
    var empty = document.querySelector('[data-certifications-empty]');

    if (!list) return;

    try {
        var response = await fetch('/api/v1/public/certifications');
        var payload = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!response.ok) {
            if (error) {
                error.textContent = 'Ocurrió un error al cargar las certificaciones.';
                error.classList.remove('hidden');
            }
            return;
        }

        var records = normalizeListPayload(payload);

        if (records.length === 0) {
            if (empty) empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = records.map(renderCertificationCard).join('');
    } catch (err) {
        if (loading) loading.classList.add('hidden');
        if (error) {
            error.textContent = 'Error de conexión. Inténtalo de nuevo.';
            error.classList.remove('hidden');
        }
    }
}

async function loadTestimonials() {
    var list = document.querySelector('[data-testimonials-list]');
    var loading = document.querySelector('[data-testimonials-loading]');
    var error = document.querySelector('[data-testimonials-error]');
    var empty = document.querySelector('[data-testimonials-empty]');

    if (!list) return;

    try {
        var response = await fetch('/api/v1/public/testimonials');
        var records = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!response.ok) {
            if (error) {
                error.textContent = 'Ocurrió un error al obtener las opiniones.';
                error.classList.remove('hidden');
            }
            return;
        }

        if (records.length === 0) {
            if (empty) empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = records.map(function (record) {
            var author = record.author ?? 'Paciente Anónimo';
            var description = record.description ?? '';
            var date = formatDate(record.date || record.created_at);
            var stars = Array.from({ length: 5 }).map(function () {
                return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.3l-6.18 3.25 1.18-6.88L2 8.9l6.91-1L12 1.6l3.09 6.3 6.91 1-5 4.77 1.18 6.88z" /></svg>';
            }).join('');

            return [
                '<div class="rounded-3xl border border-[#F5C2D6] bg-white p-6 flex flex-col justify-between shadow-sm hover:-translate-y-1 hover:shadow-md transition duration-300">',
                '<div>',
                '<div class="mb-4 flex items-center gap-1 text-amber-400">',
                stars,
                '</div>',
                '<p class="text-sm leading-6 text-slate-600 italic break-words">"', escapeHtml(description), '"</p>',
                '</div>',
                '<div class="mt-6 border-t border-slate-100 pt-4 flex items-center justify-between">',
                '<span class="text-sm font-bold text-slate-900">', escapeHtml(author), '</span>',
                '<span class="text-[10px] text-slate-500 uppercase font-semibold">', escapeHtml(date), '</span>',
                '</div>',
                '</div>'
            ].join('');
        }).join('');

    } catch (err) {
        if (loading) loading.classList.add('hidden');
        if (error) {
            console.error(err.message);
            error.textContent = 'Error al conectar con el servidor.';
            error.classList.remove('hidden');
        }
    }
}

function bootstrapLandingPage() {
    initFaqAccordion();
    loadPromotions();
    loadCertifications();
    loadTestimonials();
}

document.addEventListener('DOMContentLoaded', bootstrapLandingPage);
