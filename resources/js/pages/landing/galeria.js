import { openDialog } from '../../ui/dialog';
import { announceError, announceFailure } from '../../ui/status';

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
    var empty = document.querySelector('[data-gallery-empty]');

    if (!list) return;

    try {
        var response = await fetch('/api/v1/public/gallery-images');
        var records = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!response.ok) {
            announceError('No pudimos cargar las imágenes de la galería. Inténtalo de nuevo en un momento.');
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
                '<button type="button" data-pressable class="gallery-card group relative overflow-hidden rounded-card border border-field bg-surface p-2 text-left shadow-xs transition-shadow hover:shadow-md" data-img-url="', escapeHtml(url), '" data-img-desc="', escapeHtml(description), '" aria-label="Ampliar imagen: ', escapeHtml(description), '">',
                    '<span class="relative block aspect-square w-full overflow-hidden rounded-box bg-canvas">',
                        '<img src="', escapeHtml(url), '" alt="" class="h-full w-full object-cover" loading="lazy" />',
                        '<span class="absolute inset-x-0 bottom-0 hidden bg-ink p-3 group-hover:block group-focus-visible:block">',
                            '<span class="line-clamp-2 text-sm font-medium text-on-dark">', escapeHtml(description), '</span>',
                        '</span>',
                    '</span>',
                '</button>'
            ].join('');
        }).join('');

        initLightboxEvents();

    } catch (err) {
        if (loading) loading.classList.add('hidden');
        announceFailure(0);
    }
}

function initLightboxEvents() {
    var cards = document.querySelectorAll('.gallery-card');
    var lightbox = document.getElementById('gallery-lightbox');
    var lightboxImg = document.getElementById('lightbox-img');
    var lightboxDesc = document.getElementById('lightbox-desc');

    if (!lightbox || !lightboxImg) return;

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            var desc = card.getAttribute('data-img-desc') || '';

            lightboxImg.setAttribute('src', card.getAttribute('data-img-url'));
            lightboxImg.setAttribute('alt', desc);
            if (lightboxDesc) {
                lightboxDesc.textContent = desc;
            }

            openDialog(lightbox, card);
        });
    });

    lightbox.addEventListener('close', function () {
        lightboxImg.setAttribute('src', '');
        lightboxImg.setAttribute('alt', '');
    });
}

document.addEventListener('DOMContentLoaded', loadGallery);
