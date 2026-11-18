/*
 * Paginación (docs/design/system.md → Paginación; spec 028): el control bajo una lista de tarjetas o
 * en el pie de una tabla. Se pinta desde el `meta` que devuelve la API: el resumen "Mostrando X–Y de
 * N", "Anterior", los números (primera, actual con sus vecinas y última, con "…" en los huecos) y
 * "Siguiente". Con una sola página no se pinta nada.
 *
 * La página, la búsqueda y el filtro de una pantalla viven en la dirección (`pagina`, `busqueda`,
 * `estado`) para que recargar o volver con "Atrás" muestre lo mismo (CA8).
 */
import { announce } from './status';

const ITEM = 'inline-flex min-h-control min-w-control w-full items-center justify-center gap-1.5 rounded-control border border-field px-3 text-sm font-semibold text-ink transition-colors md:w-auto';
const LINK = ITEM + ' bg-surface hover:bg-canvas';
const CURRENT = ITEM + ' border-2 border-primary bg-primary-soft font-bold';
const UNAVAILABLE = ITEM + ' cursor-default bg-canvas text-muted';
const GAP = 'inline-flex min-h-control w-6 flex-none items-center justify-center text-sm font-semibold text-muted';

const PREVIOUS_ICON = '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"></path></svg>';
const NEXT_ICON = '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>';

/**
 * Los números que se ven: la primera, la última y la actual con dos vecinas a cada lado. Las que
 * están a dos páginas (`near`) se ocultan por debajo de 768 px. Un hueco de más de una página es "…".
 */
export function pageWindow(page, lastPage) {
    const shown = new Set([1, lastPage]);

    for (let candidate = page - 2; candidate <= page + 2; candidate++) {
        if (candidate >= 1 && candidate <= lastPage) {
            shown.add(candidate);
        }
    }

    const entries = [];
    let previous = 0;

    for (const candidate of [...shown].sort((a, b) => a - b)) {
        if (candidate - previous > 1) {
            entries.push({ type: 'gap' });
        }

        const near = Math.abs(candidate - page) === 2 && candidate !== 1 && candidate !== lastPage;
        entries.push({ type: 'page', page: candidate, near });
        previous = candidate;
    }

    return entries;
}

function pageHref(page) {
    const query = listStateQuery({ ...readListState(typeof window === 'undefined' ? '' : window.location.search), pagina: page });

    return query === '' ? '?' : query;
}

function stepMarkup(target, enabled, label, content, place) {
    if (!enabled) {
        return '<span class="' + UNAVAILABLE + ' ' + place + '" aria-disabled="true" aria-label="' + label + ', no disponible">' + content + '</span>';
    }

    return '<a class="' + LINK + ' ' + place + '" href="' + pageHref(target) + '" data-pagination-page="' + target + '" aria-label="' + label + '">' + content + '</a>';
}

/**
 * El control como marcado, desde el `meta` de la API. Cadena vacía con una sola página.
 */
export function paginationMarkup(meta, noun) {
    if (!meta || meta.last_page <= 1 || meta.total <= 0) {
        return '';
    }

    const { page, last_page: lastPage } = meta;
    const numbers = pageWindow(page, lastPage).map((entry) => {
        if (entry.type === 'gap') {
            return '<li class="flex flex-none"><span class="' + GAP + '" aria-hidden="true">…</span></li>';
        }

        const place = entry.near ? 'hidden md:flex md:flex-none' : 'flex flex-1 md:flex-none';

        if (entry.page === page) {
            return '<li class="' + place + '"><a class="' + CURRENT + '" href="' + pageHref(entry.page) + '" data-pagination-page="' + entry.page + '" aria-current="page" aria-label="Página ' + entry.page + ', actual">' + entry.page + '</a></li>';
        }

        const label = 'Página ' + entry.page + (entry.page === lastPage ? ', última' : '');

        return '<li class="' + place + '"><a class="' + LINK + '" href="' + pageHref(entry.page) + '" data-pagination-page="' + entry.page + '" aria-label="' + label + '">' + entry.page + '</a></li>';
    }).join('');

    return '<p class="text-center text-sm text-muted lg:text-left">Mostrando <strong class="font-semibold text-ink">' + meta.from + '–' + meta.to + '</strong> de <strong class="font-semibold text-ink">' + meta.total + '</strong> ' + noun + '</p>'
        + '<div class="grid grid-cols-2 gap-2 md:flex md:items-center md:justify-center">'
        + stepMarkup(page - 1, page > 1, 'Página anterior', PREVIOUS_ICON + 'Anterior', 'order-2 md:order-none')
        + '<ul class="order-1 col-span-2 flex gap-2 md:order-none">' + numbers + '</ul>'
        + stepMarkup(page + 1, page < lastPage, 'Página siguiente', 'Siguiente' + NEXT_ICON, 'order-3 md:order-none')
        + '</div>';
}

/**
 * La página, la búsqueda y el filtro de la dirección. Una página que no es un número entero mayor
 * que cero es la primera (CA9).
 */
export function readListState(search) {
    const params = new URLSearchParams(search ?? '');
    const rawPage = params.get('pagina') ?? '';
    const page = /^[1-9]\d*$/.test(rawPage) ? Number(rawPage) : 1;

    return {
        pagina: page,
        busqueda: params.get('busqueda') ?? '',
        estado: params.get('estado') ?? '',
    };
}

/**
 * La dirección de un estado: solo lo que difiere de la primera página sin búsqueda ni filtro.
 */
export function listStateQuery(state) {
    const params = new URLSearchParams();

    if (state.pagina > 1) {
        params.set('pagina', String(state.pagina));
    }

    if (state.busqueda) {
        params.set('busqueda', state.busqueda);
    }

    if (state.estado) {
        params.set('estado', state.estado);
    }

    const query = params.toString();

    return query === '' ? '' : '?' + query;
}

/**
 * Guarda el estado en la dirección sin crear entradas nuevas en el historial, conservando los
 * demás parámetros que la pantalla use (p. ej. `pestana` en Contenido).
 */
export function writeListState(state, extra = {}) {
    const url = new URL(window.location.href);

    ['pagina', 'busqueda', 'estado', ...Object.keys(extra)].forEach((key) => url.searchParams.delete(key));
    new URLSearchParams(listStateQuery(state)).forEach((value, key) => url.searchParams.set(key, value));
    Object.entries(extra).forEach(([key, value]) => {
        if (value) {
            url.searchParams.set(key, value);
        }
    });

    window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
}

/**
 * Monta el control en su `nav` (`x-ui.pagination`). `onChange(page)` pide la página; `focusTarget`
 * es el título de la lista, que recibe el foco al cambiar de página.
 */
export function mountPagination(nav, { noun, onChange, focusTarget = null }) {
    if (!nav) {
        return { render: () => {}, announcePage: () => {} };
    }

    nav.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-pagination-page]');

        if (!link || !nav.contains(link)) {
            return;
        }

        event.preventDefault();
        onChange(Number(link.dataset.paginationPage));
    });

    return {
        render(meta) {
            const html = paginationMarkup(meta, noun);
            nav.innerHTML = html;
            nav.hidden = html === '';
        },
        announcePage(meta) {
            if (focusTarget) {
                if (!focusTarget.hasAttribute('tabindex')) {
                    focusTarget.setAttribute('tabindex', '-1');
                }

                focusTarget.focus({ preventScroll: true });
                focusTarget.scrollIntoView({ block: 'start' });
            }

            if (meta && meta.last_page > 1) {
                announce('Página ' + meta.page + ' de ' + meta.last_page);
            }
        },
    };
}
