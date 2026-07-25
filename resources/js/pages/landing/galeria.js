function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadGallery() {
    var list = document.querySelector('[data-gallery-list]');
    var loading = document.querySelector('[data-gallery-loading]');
    var error = document.querySelector('[data-gallery-error]');
    var empty = document.querySelector('[data-gallery-empty]');

    if (!list) return;

    try {
        var response = await fetch('/api/v1/public/gallery-images');
        var records = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!response.ok) {
            if (error) {
                error.textContent = 'Error al cargar las imágenes de la galería.';
                error.classList.remove('hidden');
            }
            return;
        }

        if (records.length === 0) {
            if (empty) empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = records.map(function (record) {
            var url = record.url ?? '';
            var description = record.description ?? 'Imagen de Dentissa';

            return [
                '<div class="gallery-card group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-2 shadow-xs cursor-pointer hover:shadow-md hover:border-[#F5C2D6]/40 transition-all duration-300" data-img-url="', escapeHtml(url), '" data-img-desc="', escapeHtml(description), '">',
                    '<div class="aspect-square w-full overflow-hidden rounded-2xl bg-slate-50 relative">',
                        '<img src="', escapeHtml(url), '" alt="', escapeHtml(description), '" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />',
                        '<div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">',
                            '<p class="text-xs text-white font-medium line-clamp-2">', escapeHtml(description), '</p>',
                        '</div>',
                    '</div>',
                '</div>'
            ].join('');
        }).join('');

        initLightboxEvents();

    } catch (err) {
        if (loading) loading.classList.add('hidden');
        if (error) {
            error.textContent = 'No se pudo establecer conexión con el servidor.';
            error.classList.remove('hidden');
        }
    }
}

function initLightboxEvents() {
    var cards = document.querySelectorAll('.gallery-card');
    var lightbox = document.getElementById('gallery-lightbox');
    var lightboxImg = document.getElementById('lightbox-img');
    var lightboxDesc = document.getElementById('lightbox-desc');
    var closeBtn = document.getElementById('lightbox-close');

    if (!lightbox || !lightboxImg) return;

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            var url = card.getAttribute('data-img-url');
            var desc = card.getAttribute('data-img-desc');

            lightboxImg.setAttribute('src', url);
            if (lightboxDesc) {
                lightboxDesc.textContent = desc || '';
            }
            lightbox.classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // Disable background scrolling
        });
    });

    function closeLightbox() {
        lightbox.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        lightboxImg.setAttribute('src', '');
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeLightbox);
    }

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) {
            closeLightbox();
        }
    });
}

document.addEventListener('DOMContentLoaded', loadGallery);
