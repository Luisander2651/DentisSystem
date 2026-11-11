/*
 * ui-audit · what specific screens must respect (spec 016), found by the review of the 65
 * screens at 390 px (M01–M13).
 *
 *   agenda               CA11, CA25  at 390 px it does not scroll sideways, its days are read whole (M01, M02)
 *                                    and every day with appointments shows their time or number
 *   día elegido          CA11        at 390 px, touching a day brings its list into view or announces it
 *   expediente           CA27, CA31  no table scrolls sideways (M05) and the chosen record is in view (M06)
 *   listados             CA33        the first element starts within the first screen (M12)
 *   encabezado           CA34        one page header per screen (M08)
 *   tarjeta sin datos    CA34        an empty card is 320 px tall at most (M04)
 *   cabecera del panel   CA34        no gap under what the panel header shows (M11)
 *   galería del panel    CA32        a card fits in one screen, shows no internal name or address (M07)
 *                                    and is titled by its description
 *   primera pantalla     025 K1, K6  what an entry declares: `firstScreen` (selectors that must be in
 *                                    sight without scrolling), `minImage` ({ selector, minWidth,
 *                                    minHeight }: an image loaded and painted at least that big) and
 *                                    `maxCls` (the layout shift of the page at most that much)
 *
 * Dialog entries are left to checks-dialogs. Touching a day changes the screen, so it runs on a
 * visit of its own (`runFresh`).
 */

const FIRST_SCREEN = 844;
const EMPTY_CARD = 320;

/** The descriptions UiAuditSeeder gives the gallery: the panel titles each card with its own. */
const SEEDED_GALLERY = ['Sala de espera', 'Consultorio principal', 'Área de esterilización'];

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

        // A day whose name says it has appointments shows their time or their number, for the
        // eyes and not only for a screen reader (text of 1 px is for the reader).
        const silent = [...document.querySelectorAll('#calendar-grid [data-agenda-date]')].filter((day) => {
            const count = Number((day.getAttribute('aria-label') ?? '').match(/(\d+) citas?$/)?.[1] ?? 0);
            const shown = [...day.querySelectorAll('*')].filter((element) => A.visible(element) && element.getBoundingClientRect().width > 2)
                .map((element) => [...element.childNodes].filter((node) => node.nodeType === Node.TEXT_NODE).map((node) => node.textContent).join('').trim())
                .join(' ');

            return count > 0 && !shown.split(/\s+/).includes(String(count)) &&!/\d{1,2}:\d{2}/.test(shown);
        });

        if (silent.length > 0) {
            fail('día con citas que no las muestra', `${silent.length}: ${silent.slice(0, 3).map((day) => day.getAttribute('aria-label')).join('; ')}`);
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

        // Without the empty card in front of it the measure would say nothing: that is a failure.
        if (!list || list.querySelectorAll('[data-appointment-id]').length > 0) {
            fail('precondición no cumplida', list ? '"Citas para hoy" tiene citas en un día que debía estar vacío' : 'no está la lista "Citas para hoy"');
        } else if (height > limits.emptyCard) {
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

        // The seeded images are known: each one is titled by its description, word for word.
        const titles = [...document.querySelectorAll(`${lists['contenido-galeria']} h1, ${lists['contenido-galeria']} h2, ${lists['contenido-galeria']} h3, ${lists['contenido-galeria']} h4`)]
            .map((heading) => heading.textContent.trim());
        const untitled = limits.seededGallery.filter((description) => !titles.includes(description));

        if (untitled.length > 0) {
            fail('tarjeta de galería sin su descripción como título', `ninguna tarjeta se titula ${untitled.map((description) => `"${description}"`).join(', ')}`);
        }
    }

    return found;
}

/**
 * Spec 025 (K1, K6): what an entry declares about its first screen. Positions are taken from the
 * top of the document, so a page already scrolled does not hide a failure.
 */
function firstScreenInPage(declared) {
    const A = window.__audit;
    const found = [];
    const fail = (rule, message) => found.push({ rule, message });
    const visibleOne = (selector) => [...document.querySelectorAll(selector)].find(A.visible) ?? null;

    for (const selector of declared.firstScreen ?? []) {
        const element = visibleOne(selector);

        if (element === null) {
            fail('fuera de la primera pantalla', `no hay nada a la vista con ${selector}`);
            continue;
        }

        const box = element.getBoundingClientRect();
        const top = box.top + scrollY;
        const bottom = box.bottom + scrollY;

        if (top < 0 || bottom > innerHeight + 1) {
            fail('fuera de la primera pantalla', `${A.describe(element)} termina a ${Math.round(bottom)} px y la pantalla mide ${innerHeight} px (${selector})`);
        }
    }

    if (declared.minImage) {
        const { selector, minWidth = 0, minHeight = 0 } = declared.minImage;
        const image = visibleOne(selector);
        const loaded = image instanceof HTMLImageElement && image.complete && image.naturalWidth + image.naturalHeight > 0;

        if (!loaded) {
            fail('imagen sin cargar', image ? `${A.describe(image)} no terminó de cargar (${selector})` : `no hay ninguna imagen a la vista (${selector})`);
        } else {
            const box = image.getBoundingClientRect();

            if (box.width + 0.5 < minWidth || box.height + 0.5 < minHeight) {
                fail('imagen más pequeña de lo esperado', `${A.describe(image)} mide ${Math.round(box.width)} × ${Math.round(box.height)} px y debía medir al menos ${minWidth} × ${minHeight} px`);
            }
        }
    }

    if (declared.maxCls !== undefined) {
        if (window.__auditCls === null) {
            fail('salto al cargar sin medir', 'el navegador no mide los saltos de la página');
        } else if (window.__auditCls > declared.maxCls) {
            fail('salto al cargar', `la página saltó ${window.__auditCls.toFixed(3)} y el máximo es ${declared.maxCls}`);
        }
    }

    return found;
}

export async function run(page, entry) {
    if (entry.dialog !== undefined) {
        return [];
    }

    const found = await page.evaluate(measureInPage, { id: entry.id, screen: entry.screen, path: entry.path }, page.width < 768, LISTS, { firstScreen: FIRST_SCREEN, emptyCard: EMPTY_CARD, seededGallery: SEEDED_GALLERY });

    if (entry.firstScreen === undefined && entry.minImage === undefined && entry.maxCls === undefined) {
        return found;
    }

    if (entry.minImage) {
        // The image comes from the API after the page: wait for it, as the dialogs do (3 s).
        await page.waitFor(`(() => { const image = document.querySelector(${JSON.stringify(entry.minImage.selector)}); return image instanceof HTMLImageElement && image.complete && image.naturalWidth > 0; })()`, { timeout: 3000, what: 'la imagen' }).catch(() => {});
    }

    return found.concat(await page.evaluate(firstScreenInPage, { firstScreen: entry.firstScreen, minImage: entry.minImage, maxCls: entry.maxCls }));
}

/** Touches the first day with appointments that is not the one already chosen. */
function touchDayInPage() {
    const day = [...document.querySelectorAll('#calendar-grid [data-agenda-date]')]
        .find((candidate) => /[1-9]\d* citas?$/.test(candidate.getAttribute('aria-label') ?? '') && candidate.getAttribute('aria-pressed') !== 'true');

    if (!day) {
        return null;
    }

    const said = [];
    new MutationObserver(() => {
        for (const region of document.querySelectorAll('[role=status], [role=alert], [aria-live]')) {
            const text = region.textContent.replace(/\s+/g, ' ').trim();

            if (text !== '') {
                said.push(text);
            }
        }
    }).observe(document.body, { subtree: true, childList: true, characterData: true });
    window.__daySaid = said;
    window.scrollTo(0, 0);
    day.click();

    return day.getAttribute('aria-label');
}

function chosenDayInPage() {
    const title = document.querySelector('[data-agenda-day-title]');
    const box = title?.getBoundingClientRect();

    return {
        inView: Boolean(box) && box.top >= 0 && box.bottom <= window.innerHeight,
        announced: (window.__daySaid ?? []).some((text) => /cita/i.test(text)),
    };
}

/** CA11 · on a phone, the list of the touched day does not stay out of sight and unsaid. */
export async function runFresh(page, entry) {
    if (entry.id !== 'agenda-con-citas' || page.width >= 768) {
        return [];
    }

    const day = await page.evaluate(touchDayInPage);

    if (day === null) {
        return [{ rule: 'precondición no cumplida', message: 'no hay ningún día con citas que tocar' }];
    }

    await page.settle();
    const chosen = await page.evaluate(chosenDayInPage);

    return chosen.inView || chosen.announced
        ? []
        : [{ rule: 'el día elegido no responde a la vista', message: `tras tocar "${day}" su lista queda fuera de la pantalla y no se anuncia nada` }];
}
