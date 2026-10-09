/*
 * ui-audit · what every screen and every dialog must respect (spec 016).
 *
 *   desborde                 CA28, RNF  the page does not scroll sideways
 *   control táctil           CA9        at 390 px, every control measures 44 × 44 px or more
 *   tamaño de control        CA10       buttons and fields share one height and the radii of the scale
 *   letra                    CA29       fields from 16 px at 390 px, no text under 12 px
 *   contraste                CA6, CA7   text and placeholders 4.5:1 (3:1 when large), field borders 3:1
 *   foco                     CA8        one focus style, 3 px with 2 px of offset, 3:1, also on dark surfaces
 *   acción principal         CA3, CA4   one background, dark text on it and one hover
 *   recorte                  CA28       nothing with information is truncated or cut by the screen edge
 *
 * The values come from the tokens of the page (`--color-primary`, `--spacing-control`…); while
 * resources/css/app.css does not declare them yet, from the plan.
 */
import { contrastOf } from './audit-lib.mjs';

const FALLBACK_TOKENS = {
    '--color-primary': '#d75078',
    '--color-primary-hover': '#dc6588',
    '--color-primary-soft': '#fbe9ee',
    '--color-secondary': '#f2b0a6',
    '--color-ink': '#0b1120',
    '--color-canvas': '#f8fafc',
    '--color-surface': '#ffffff',
    '--color-on-dark': '#ffffff',
    '--color-line': '#e2e8f0',
    '--color-danger': '#b91c1c',
    '--color-danger-soft': '#fef2f2',
    '--color-success-soft': '#ecfdf5',
    '--color-warning-soft': '#fffbeb',
    '--color-info-soft': '#eff6ff',
    '--color-whatsapp': '#25d366',
    '--radius-control': '12px',
    '--radius-box': '16px',
    '--radius-card': '24px',
    '--spacing-control': '44px',
};

const FOCUS = { width: 3, offset: 2 };

/** Runs inside the page: everything that can be measured without touching it. */
function measureInPage(dialogSelector, phone, fallback) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);

    if (!root) {
        return null;
    }

    const rootStyle = getComputedStyle(document.documentElement);
    const token = (name) => rootStyle.getPropertyValue(name).trim() || fallback[name];
    const pixels = (name) => parseFloat(token(name));
    const control = pixels('--spacing-control');
    const radii = ['--radius-control', '--radius-box', '--radius-card'].map(pixels);
    const viewport = document.documentElement.clientWidth;
    const elements = [...root.querySelectorAll('*')].filter(A.visible);
    const hasOwnText = (element) => [...element.childNodes].some((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '');
    const insideScroller = (element) => {
        for (let node = element.parentElement; node && node !== document.documentElement; node = node.parentElement) {
            if (['auto', 'scroll'].includes(getComputedStyle(node).overflowX)) {
                return true;
            }
        }

        return false;
    };
    const found = { desborde: [], tactil: [], tamano: [], letraCampo: [], letraMinima: [], contraste: [], ejemplo: [], borde: [], truncado: [], recortado: [], sobreImagen: [] };

    if (!dialogSelector) {
        const width = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth);

        if (width > viewport + 1) {
            found.desborde.push(`la página mide ${width} px de ancho en una ventana de ${viewport} px`);
        }
    }

    for (const element of elements) {
        const style = getComputedStyle(element);
        const box = element.getBoundingClientRect();
        const tag = element.tagName;
        const type = (element.getAttribute('type') ?? '').toLowerCase();
        const isField = (tag === 'INPUT' && !['hidden', 'checkbox', 'radio', 'file', 'range', 'color', 'submit', 'button', 'reset'].includes(type)) || tag === 'SELECT' || tag === 'TEXTAREA';
        const isButton = tag === 'BUTTON' || element.getAttribute('role') === 'button' || (tag === 'INPUT' && ['submit', 'button', 'reset'].includes(type));
        const disabled = element.closest(':disabled, [aria-disabled="true"]') !== null;
        const insideControl = element.parentElement?.closest('a[href], button, [role=button], summary, label') ?? null;

        // CA9 · touch targets
        if (phone && !disabled) {
            const loneLink = tag === 'A' && element.hasAttribute('href')
                && (style.display !== 'inline' || element.closest('nav, ul, ol, menu') !== null || element.parentElement.textContent.trim() === element.textContent.trim());
            const pressed = !A.isControl(element) && !insideControl && style.cursor === 'pointer' && getComputedStyle(element.parentElement).cursor !== 'pointer';
            const checkable = tag === 'INPUT' && ['checkbox', 'radio'].includes(type);

            if ((isButton || isField || loneLink || pressed || checkable || tag === 'SUMMARY' || (tag === 'INPUT' && type === 'file')) && !(insideControl && tag !== 'INPUT')) {
                const label = checkable ? element.closest('label') ?? (element.id ? document.querySelector(`label[for="${element.id}"]`) : null) : null;
                const target = (label ?? element).getBoundingClientRect();

                if (target.width < 43.5 || target.height < 43.5) {
                    found.tactil.push(`${A.describe(element)} mide ${Math.round(target.width)} × ${Math.round(target.height)} px`);
                }
            }
        }

        // CA10 · one height and the radii of the scale
        if (isButton || isField) {
            const radius = parseFloat(style.borderTopLeftRadius);
            const round = radius >= Math.min(box.width, box.height) / 2 - 0.5;
            const pressable = element.hasAttribute('data-pressable');
            const heightOk = pressable || tag === 'TEXTAREA' ? box.height >= control - 0.5 : Math.abs(box.height - control) <= 0.5;
            const radiusOk = pressable ? radii.includes(radius) || round : isField ? radius === radii[0] : radius === radii[0] || round;

            if (!heightOk || !radiusOk) {
                found.tamano.push(`${A.describe(element)} mide ${Math.round(box.height)} px de alto con radio ${Math.round(radius)} px`);
            }
        }

        // CA29 · font sizes
        const fontSize = parseFloat(style.fontSize);

        if (isField && phone && fontSize < 16) {
            found.letraCampo.push(`${A.describe(element)} usa letra de ${fontSize} px`);
        }

        if (hasOwnText(element) && fontSize < 12) {
            found.letraMinima.push(`${A.describe(element)} usa letra de ${fontSize} px`);
        }

        // CA6 · contrast of text, of what a field shows and of its placeholder
        if ((hasOwnText(element) || isField) && !disabled && tag !== 'OPTION' && tag !== 'OPTGROUP') {
            const backdrop = A.backdrop(element);
            const large = fontSize >= 24 || (fontSize >= 18.66 && Number(style.fontWeight) >= 700);
            const minimum = large ? 3 : 4.5;
            const color = A.rgba(style.color);

            if (backdrop.image) {
                if (hasOwnText(element) && found.sobreImagen.length < 12) {
                    found.sobreImagen.push({ what: A.describe(element), color: color.slice(0, 3), minimum, box: { x: box.x + scrollX, y: box.y + scrollY, width: box.width, height: box.height } });
                }
            } else {
                const ratio = A.worstContrast(color, backdrop.tones);

                if (ratio < minimum - 0.005) {
                    found.contraste.push(`${A.describe(element)} ${ratio.toFixed(2)}:1 (mín. ${minimum})`);
                }

                if (isField && element.getAttribute('placeholder')) {
                    const example = A.worstContrast(A.rgba(getComputedStyle(element, '::placeholder').color), backdrop.tones);

                    if (example < 4.5 - 0.005) {
                        found.ejemplo.push(`${A.describe(element)} ${example.toFixed(2)}:1`);
                    }
                }
            }
        }

        // CA7 · the boundary of a field
        if (isField && !disabled) {
            const outside = A.backdrop(element.parentElement).tones;
            const edge = parseFloat(style.borderTopWidth) > 0
                ? A.worstContrast(A.rgba(style.borderTopColor), outside)
                : Math.min(...outside.map((tone) => A.contrast(A.over(A.rgba(style.backgroundColor), tone), tone)));

            if (edge < 3 - 0.005) {
                found.borde.push(`${A.describe(element)} ${edge.toFixed(2)}:1`);
            }
        }

        // CA28 · nothing truncated, nothing cut by the edge of the screen
        if (hasOwnText(element) && !isField) {
            const cutAcross = element.scrollWidth > element.clientWidth + 1 && ['hidden', 'clip'].includes(style.overflowX);
            const cutDown = element.scrollHeight > element.clientHeight + 1 && ['hidden', 'clip'].includes(style.overflowY);

            if (cutAcross || cutDown) {
                found.truncado.push(A.describe(element));
            }
        }

        // A file picker writes its own text; it is cut when the field is narrower than that text.
        if (tag === 'INPUT' && type === 'file') {
            const natural = element.cloneNode();
            natural.removeAttribute('id');
            natural.style.cssText = 'position:absolute;visibility:hidden;width:auto;min-width:0;max-width:none;';
            natural.style.font = style.font;
            natural.style.padding = style.padding;
            element.after(natural);
            const needed = natural.getBoundingClientRect().width;
            natural.remove();

            if (box.width < needed - 1) {
                found.truncado.push(`${A.describe(element)}: selector de archivo de ${Math.round(box.width)} px para un texto de ${Math.round(needed)} px`);
            }
        }

        // Cut by a box that hides what overflows it (the badges over the main picture).
        if (hasOwnText(element) || tag === 'IMG') {
            for (let node = element.parentElement; node && node !== document.body; node = node.parentElement) {
                const clip = getComputedStyle(node);
                const frame = node.getBoundingClientRect();
                const across = ['hidden', 'clip'].includes(clip.overflowX) && (box.left < frame.left - 1 || box.right > frame.right + 1);
                const down = ['hidden', 'clip'].includes(clip.overflowY) && (box.top < frame.top - 1 || box.bottom > frame.bottom + 1);

                // Entirely out of the box is hidden (a folded answer), not cut.
                const shown = box.right > frame.left && box.left < frame.right && box.bottom > frame.top && box.top < frame.bottom && frame.height > 1 && frame.width > 1;

                if (across || down) {
                    if (shown && hasOwnText(element)) {
                        found.recortado.push(`${A.describe(element)} sobresale de la caja que lo recorta`);
                    }

                    break;
                }

                if (['auto', 'scroll'].includes(clip.overflowX) || ['auto', 'scroll'].includes(clip.overflowY)) {
                    break;
                }
            }
        }

        // A box with something to read inside (a badge) counts as much as the text itself.
        const paintedBox = box.width <= viewport && element.textContent.trim() !== '' && element.getAttribute('aria-hidden') !== 'true'
            && (A.rgba(style.backgroundColor)[3] > 0.5 || parseFloat(style.borderTopWidth) > 0);

        if (phone && (hasOwnText(element) || isField || isButton || tag === 'IMG' || paintedBox) && (box.right > viewport + 1 || box.left < -1) && !insideScroller(element) && style.position !== 'fixed') {
            found.recortado.push(`${A.describe(element)} llega a x=${Math.round(box.right)} px`);
        }
    }

    return found;
}

/** The controls whose focus is measured: one of each kind, so a screen costs the same with 5 rows or 500. */
function focusableInPage(dialogSelector) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);
    const kinds = new Map();

    for (const element of root ? root.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, summary, [tabindex]:not([tabindex="-1"])') : []) {
        const kind = `${element.tagName} ${element.getAttribute('type') ?? ''} ${element.className}`;

        if (A.visible(element) && !element.matches(':disabled') && !kinds.has(kind)) {
            kinds.set(kind, element);
        }
    }

    return [...kinds.values()].slice(0, 40);
}

function focusStyle() {
    const A = window.__audit;
    const style = getComputedStyle(this);
    const outside = A.backdrop(this.parentElement ?? this);

    return {
        what: A.describe(this),
        style: style.outlineStyle,
        width: parseFloat(style.outlineWidth),
        offset: parseFloat(style.outlineOffset),
        contrast: outside.image ? null : A.worstContrast(A.rgba(style.outlineColor), outside.tones),
    };
}

/** Buttons and button-like links painted with a background of their own. */
function filledActionsInPage(dialogSelector) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);
    const kinds = new Map();

    for (const element of root ? root.querySelectorAll('button, a[href], [role=button], input[type=submit]') : []) {
        const color = A.rgba(getComputedStyle(element).backgroundColor);
        const kind = `${element.tagName} ${element.className}`;

        if (A.visible(element) && color[3] >= 0.9 && !element.matches(':disabled') && !kinds.has(kind)) {
            kinds.set(kind, element);
        }
    }

    return [...kinds.values()].slice(0, 40);
}

function paintedColors() {
    const A = window.__audit;
    const style = getComputedStyle(this);

    return { what: A.describe(this), background: A.rgba(style.backgroundColor).slice(0, 3), color: A.rgba(style.color).slice(0, 3) };
}

function tokensInPage(fallback) {
    const style = getComputedStyle(document.documentElement);

    return Object.fromEntries(Object.keys(fallback)
        .filter((name) => name.startsWith('--color-'))
        .map((name) => [name, window.__audit.rgba(style.getPropertyValue(name).trim() || fallback[name]).slice(0, 3)]));
}

const hex = (color) => `#${color.map((value) => Math.round(value).toString(16).padStart(2, '0')).join('')}`;
const same = (a, b) => a.every((value, index) => Math.abs(value - b[index]) <= 2);
const summary = (items) => `${items.length}: ${items.slice(0, 4).join('; ')}${items.length > 4 ? ` (y ${items.length - 4} más)` : ''}`;

export async function run(page, entry) {
    const selector = entry.dialogSelector ?? null;
    const found = await page.evaluate(measureInPage, selector, page.width < 768, FALLBACK_TOKENS);

    if (found === null) {
        return [];
    }

    // CA6 over a picture: only the painted pixels can tell, once the text itself is out of the way.
    if (found.sobreImagen.length > 0) {
        await page.evaluate(() => {
            const style = document.createElement('style');
            style.id = 'ui-audit-no-text';
            style.textContent = '* { color: transparent !important; text-shadow: none !important; }';
            document.head.append(style);
        });
    }

    for (const text of found.sobreImagen) {
        const range = await page.tones(text.box);
        const ratio = Math.min(contrastOf(text.color, range.darkest), contrastOf(text.color, range.lightest));

        if (ratio < text.minimum - 0.005) {
            found.contraste.push(`${text.what} ${ratio.toFixed(2)}:1 sobre una imagen (mín. ${text.minimum})`);
        }
    }

    await page.evaluate(() => document.getElementById('ui-audit-no-text')?.remove());

    // CA8 · focus
    const focus = { missing: [], off: [], faint: [] };

    for (const handle of await page.handles(focusableInPage, selector)) {
        const measured = await page.withPseudoState(handle, ['focus', 'focus-visible'], focusStyle);

        if (measured.style === 'none' || measured.width < 1) {
            focus.missing.push(measured.what);
        } else if (measured.width !== FOCUS.width || measured.offset !== FOCUS.offset) {
            focus.off.push(`${measured.what} (${measured.width} px, separación ${measured.offset} px)`);
        } else if (measured.contrast !== null && measured.contrast < 3 - 0.005) {
            focus.faint.push(`${measured.what} ${measured.contrast.toFixed(2)}:1`);
        }
    }

    // CA3 and CA4 · the main action
    const tokens = await page.evaluate(tokensInPage, FALLBACK_TOKENS);
    const quiet = ['surface', 'canvas', 'primary-soft', 'line', 'danger', 'danger-soft', 'success-soft', 'warning-soft', 'info-soft', 'whatsapp', 'ink']
        .map((name) => tokens[`--color-${name}`]);
    const action = { foreign: [], lightText: [], hover: [] };

    for (const handle of await page.handles(filledActionsInPage, selector)) {
        const rest = await page.call(handle, paintedColors);

        if (quiet.some((color) => same(color, rest.background))) {
            continue;
        }

        if (!same(rest.background, tokens['--color-primary'])) {
            action.foreign.push(`${rest.what} ${hex(rest.background)}`);

            continue;
        }

        if (!same(rest.color, tokens['--color-ink'])) {
            action.lightText.push(`${rest.what} con texto ${hex(rest.color)}`);
        }

        // A touch screen has no hover: Tailwind only paints it where the device can hover.
        if (page.width < 768) {
            continue;
        }

        const hovered = await page.withPseudoState(handle, ['hover'], paintedColors);

        if (!same(hovered.background, tokens['--color-primary-hover']) || contrastOf(hovered.color, hovered.background) < 4.5) {
            action.hover.push(`${rest.what} pasa a ${hex(hovered.background)}`);
        }
    }

    return [
        ['desborde', found.desborde],
        ['control táctil menor de 44 × 44 px', found.tactil],
        ['tamaño de control fuera de la escala', found.tamano],
        ['letra de campo menor de 16 px', found.letraCampo],
        ['texto menor de 12 px', found.letraMinima],
        ['contraste de texto', found.contraste],
        ['contraste del texto de ejemplo', found.ejemplo],
        ['contraste del borde de campo', found.borde],
        ['texto truncado', found.truncado],
        ['recortado por el borde de la pantalla', found.recortado],
        ['sin indicador de foco', focus.missing],
        ['foco distinto del sistema (3 px con 2 px de separación)', focus.off],
        ['contraste del foco', focus.faint],
        ['fondo de acción fuera del sistema', action.foreign],
        ['acción principal sin texto en tinta', action.lightText],
        ['hover de la acción principal fuera del sistema', action.hover],
    ].filter(([, items]) => items.length > 0).map(([rule, items]) => ({ rule, message: summary(items) }));
}
