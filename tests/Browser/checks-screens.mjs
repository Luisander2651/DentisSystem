/*
 * ui-audit · what specific screens must respect (spec 016), found by the review of the 65
 * screens at 390 px (M01–M13).
 *
 *   agenda               CA11, CA25  at 390 px it does not scroll sideways and its days are read whole (M01, M02)
 *   expediente           CA27, CA31  no table scrolls sideways (M05) and the chosen record is in view (M06)
 *   listados             CA33        the first element starts within the first screen (M12)
 *   encabezado           CA34        one page header per screen (M08)
 *   tarjeta sin datos    CA34        an empty card is 320 px tall at most (M04)
 *   cabecera del panel   CA34        no gap under what the panel header shows (M11)
 *   galería del panel    CA32        a card fits in one screen and shows no internal name or address (M07)
 *
 * Dialog entries are left to checks-dialogs.
 */

const FIRST_SCREEN = 844;
const EMPTY_CARD = 320;

const LISTS = {
    pacientes: '#patients-list',
    usuarios: '#users-table',
    tratamientos: '#treatments-list',
    expedientes: '#records-table-container',
    'contenido-galeria': '[data-gallery-list]',
    'contenido-promociones': '[data-promotions-list]',
    'contenido-certificaciones': '[data-certifications-list]',
    'contenido-testimonios': '[data-testimonials-list]',
};

function measureInPage(entry, phone, lists, limits) {
    const A = window.__audit;
    const found = [];
    const fail = (rule, message) => found.push({ rule, message });
    const first = (selector) => [...document.querySelectorAll(selector)].find(A.visible) ?? null;
    const firstChild = (selector) => [...(first(selector)?.children ?? [])].find(A.visible) ?? null;
    const top = (element) => Math.round(element.getBoundingClientRect().top + scrollY);
    const panel = first('[data-sidebar-root]') !== null;

    // CA11, CA25 · the agenda on a phone
    if (entry.screen === 'agenda' && phone) {
        const width = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth);

        if (width > document.documentElement.clientWidth + 1) {
            fail('la agenda se desplaza de lado', `mide ${width} px de ancho`);
        }

        const cut = [...document.querySelectorAll('#calendar-grid *')].filter((element) => A.visible(element)
            && element.scrollWidth > element.clientWidth + 1
            && ['hidden', 'clip'].includes(getComputedStyle(element).overflowX)
            && element.textContent.trim() !== '');

        if (cut.length > 0) {
            fail('citas del calendario cortadas', `${cut.length}: ${cut.slice(0, 3).map((element) => A.describe(element)).join('; ')}`);
        }
    }

    // CA27, CA31 · the record of a patient on a phone
    if (entry.screen === 'expedientes' && phone) {
        const detail = first('#record-detail-section');
        const scrollers = [...(detail?.querySelectorAll('*') ?? [])].filter((element) => A.visible(element)
            && element.scrollWidth > element.clientWidth + 1
            && ['auto', 'scroll'].includes(getComputedStyle(element).overflowX));

        if (scrollers.length > 0) {
            fail('registros del expediente que se desplazan de lado', `${scrollers.length}: ${scrollers.slice(0, 3).map((element) => `${A.describe(element)} (${element.scrollWidth} px en ${element.clientWidth})`).join('; ')}`);
        }

        if (entry.path.split('/').length > 2 && (!detail || top(detail) >= limits.firstScreen)) {
            fail('el expediente elegido no queda a la vista', detail ? `empieza en y=${top(detail)} px` : 'no se muestra');
        }
    }

    // CA33 · the first element of a listing
    if (lists[entry.screen] && phone && !(entry.screen === 'expedientes' && entry.path.split('/').length > 2)) {
        const item = firstChild(lists[entry.screen]);

        if (!item) {
            fail('listado sin elementos', `${lists[entry.screen]} no muestra ninguno`);
        } else if (top(item) >= limits.firstScreen) {
            fail('el listado empieza fuera de la primera pantalla', `su primer elemento empieza en y=${top(item)} px`);
        }
    }

    // CA34 · one header per screen
    if (panel) {
        const heroes = [...document.querySelectorAll('[id^="page-hero-"]')].filter(A.visible).length;
        const titles = [...document.querySelectorAll('h1')].filter(A.visible).length;

        if (heroes > 1 || titles !== 1) {
            fail('encabezados de página', `${heroes} encabezados de página y ${titles} títulos principales a la vista`);
        }
    }

    // CA34 · an empty card
    if (entry.id === 'agenda-hoy-sin-citas' && phone) {
        const list = first('#today-appointments-container');
        let card = list;

        while (card && card !== document.body && !(parseFloat(getComputedStyle(card).borderTopWidth) > 0 && parseFloat(getComputedStyle(card).borderTopLeftRadius) > 0)) {
            card = card.parentElement;
        }

        const height = Math.round((card ?? list)?.getBoundingClientRect().height ?? 0);

        if (list && list.querySelectorAll('[data-appointment-id]').length === 0 && height > limits.emptyCard) {
            fail('tarjeta sin datos demasiado alta', `"Citas para hoy" mide ${height} px sin citas`);
        }
    }

    // CA34 · the header of the panel on a phone
    if (panel && phone) {
        const header = first('[data-sidebar-root]');
        const style = getComputedStyle(header);
        const content = Math.max(...[...header.querySelectorAll('*')].filter(A.visible).map((element) => element.getBoundingClientRect().bottom));
        const gap = Math.round(header.getBoundingClientRect().bottom - content - parseFloat(style.paddingBottom) - parseFloat(style.borderBottomWidth));

        if (gap > 8) {
            fail('cabecera del panel con hueco', `${gap} px vacíos bajo su contenido`);
        }
    }

    // CA32 · the gallery card of the panel on a phone
    if (entry.screen === 'contenido-galeria' && phone) {
        const card = firstChild(lists['contenido-galeria']);

        if (card) {
            const height = Math.round(card.getBoundingClientRect().height);
            const text = card.innerText;
            const title = card.querySelector('h1, h2, h3, h4')?.textContent.trim() ?? '';

            if (height > limits.firstScreen) {
                fail('tarjeta de galería más alta que la pantalla', `mide ${height} px`);
            }

            if (/\.(svg|png|jpe?g|webp|avif|gif)\b/i.test(text) || /\/storage\/|https?:\/\//i.test(text)) {
                fail('tarjeta de galería con nombre o dirección internos', (text.match(/\S*(?:\/storage\/|https?:\/\/|\.(?:svg|png|jpe?g|webp|avif|gif))\S*/i) ?? [''])[0].slice(0, 80));
            }

            if (title === '' || /^(imagen|galer[ií]a)\s*#?\d*$/i.test(title)) {
                fail('tarjeta de galería sin su descripción como título', title === '' ? 'no tiene título' : `se titula "${title}"`);
            }
        }
    }

    return found;
}

export function run(page, entry) {
    if (entry.dialog !== undefined) {
        return [];
    }

    return page.evaluate(measureInPage, { id: entry.id, screen: entry.screen, path: entry.path }, page.width < 768, LISTS, { firstScreen: FIRST_SCREEN, emptyCard: EMPTY_CARD });
}
