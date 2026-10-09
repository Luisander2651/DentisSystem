/*
 * ui-audit · what a person who does not see the screen, or does not use a mouse, needs (spec 016).
 *
 *   nombre accesible     CA35  every control and every image with information has a name
 *   etiqueta             CA36  every field has a visible label; required fields are told apart
 *   error de campo       CA37  a field error is tied to its field
 *   región de estado     CA37  a status region is in the accessibility tree: one per screen,
 *                              and one inside every open dialog
 *   contraste no textual CA38  action icons and control borders reach 3:1
 *   solo color           CA38  no state is told by colour alone
 *   teclado              CA40  whatever is pressed is a control, or behaves like one
 *   anuncio              CA37  saving and failing to save announce a message, in our own
 *                              Spanish and not in the words of the API (A63)
 *
 * The last one sends the form of a dialog, so it runs on a visit of its own (`runFresh`) with
 * the API answered from here: nothing reaches the database.
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const NAMED_ROLES = ['button', 'link', 'textbox', 'searchbox', 'combobox', 'listbox', 'checkbox', 'radio', 'switch', 'slider', 'spinbutton', 'tab', 'menuitem', 'image', 'img'];
const KEYBOARD_ROLES = ['button', 'link', 'tab', 'checkbox', 'switch', 'menuitem', 'option', 'radio'];

/** The words of UiCopyTest's closed list, read from the same file. */
function forbiddenWords() {
    const file = join(dirname(fileURLToPath(import.meta.url)), '../Modules/Core/Unit/Design/forbidden-words.php');
    const source = readFileSync(file, 'utf8');
    const technical = source.slice(source.indexOf("'technical'"));

    return {
        spelling: [...source.slice(0, source.indexOf("'technical'")).matchAll(/^\s+'([^']+)' => '/gm)].map((match) => match[1]),
        technical: [...technical.matchAll(/^\s+'([^']+)',/gm)].map((match) => match[1]),
    };
}

function measureInPage(dialogSelector, entryId, needsRegion) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);
    const found = { etiqueta: [], obligatorio: [], error: [], region: [], borde: [], icono: [], color: [] };

    if (!root) {
        return found;
    }

    const visible = [...root.querySelectorAll('*')].filter(A.visible);
    const labelOf = (field) => field.closest('label') ?? (field.id ? document.querySelector(`label[for="${CSS.escape(field.id)}"]`) : null);
    const fields = visible.filter((element) => element.matches('input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=reset]), select, textarea'));

    // CA36 · a visible label per field
    for (const field of fields) {
        const label = labelOf(field);

        if (!label || !A.visible(label) || label.textContent.trim() === '') {
            found.etiqueta.push(A.describe(field));
        }
    }

    // CA36 · required fields told apart, form by form
    const forms = new Map();

    for (const field of fields) {
        const form = field.closest('form') ?? root;
        forms.set(form, [...(forms.get(form) ?? []), field]);
    }

    for (const [form, members] of forms) {
        const isRequired = (field) => field.required || field.getAttribute('aria-required') === 'true';
        const text = (field) => labelOf(field)?.textContent ?? '';
        const required = members.filter(isRequired);
        const optional = members.filter((field) => !isRequired(field));
        const marked = required.every((field) => /\*|obligatori/i.test(text(field)));
        const others = optional.every((field) => /opcional/i.test(text(field)));

        if (required.length > 0 && optional.length > 0 && !marked && !others) {
            found.obligatorio.push(`${A.describe(form)}: ${required.length} obligatorios y ${optional.length} opcionales sin distinguir`);
        }
    }

    // CA37 · a field error is tied to its field
    const invalid = fields.filter((field) => field.getAttribute('aria-invalid') === 'true');

    for (const field of invalid) {
        const described = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));

        if (!described.some((node) => node && A.visible(node) && node.textContent.trim() !== '')) {
            found.error.push(`${A.describe(field)} está marcado con error pero ningún mensaje lo describe`);
        }
    }

    if (entryId.endsWith('-validacion') && invalid.length === 0) {
        found.error.push('hay un error de validación a la vista y ningún campo está marcado con él');
    }

    // CA37 · the status region
    const reachable = (region) => {
        for (let node = region; node instanceof Element; node = node.parentElement) {
            const style = getComputedStyle(node);

            if (style.display === 'none' || style.visibility === 'hidden' || node.getAttribute('aria-hidden') === 'true' || node.hasAttribute('inert')) {
                return false;
            }
        }

        return true;
    };
    const regions = [...root.querySelectorAll('[role=status]')].filter(reachable);

    if (dialogSelector) {
        if (regions.length === 0) {
            found.region.push('el diálogo abierto no tiene región de estado propia');
        }
    } else if (needsRegion) {
        const outside = regions.filter((region) => region.closest('dialog, [role=dialog]') === null);

        if (outside.length !== 1) {
            found.region.push(`la pantalla tiene ${outside.length} regiones de estado fuera de los diálogos (debe tener una)`);
        }
    }

    for (const element of visible) {
        const style = getComputedStyle(element);
        const box = element.getBoundingClientRect();
        const control = element.matches('button, a[href], [role=button], select, summary');

        if (control && !element.matches(':disabled')) {
            const outside = A.backdrop(element.parentElement);
            const fill = A.rgba(style.backgroundColor);
            const flat = outside.image || Math.max(...outside.tones.map((tone) => A.contrast(A.over(fill, tone), tone))) < 1.1;

            // CA38 · a control drawn only by its border
            if (flat && !outside.image && parseFloat(style.borderTopWidth) > 0 && A.rgba(style.borderTopColor)[3] > 0) {
                const edge = A.worstContrast(A.rgba(style.borderTopColor), outside.tones);

                if (edge < 3 - 0.005) {
                    found.borde.push(`${A.describe(element)} ${edge.toFixed(2)}:1`);
                }
            }

            // CA38 · a control that only shows an icon
            if (element.textContent.trim() === '' && element.querySelector('svg, img')) {
                const behind = A.backdrop(element);
                const icon = behind.image ? null : A.worstContrast(A.rgba(style.color), behind.tones);

                if (icon !== null && icon < 3 - 0.005) {
                    found.icono.push(`${A.describe(element)} ${icon.toFixed(2)}:1`);
                }
            }
        }

        // CA38 · a coloured dot that says something on its own
        const dot = box.width <= 16 && box.height <= 16 && parseFloat(style.borderTopLeftRadius) >= box.width / 2 - 0.5
            && A.rgba(style.backgroundColor)[3] > 0.5 && element.textContent.trim() === '' && element.children.length === 0;

        if (dot && element.closest('[aria-hidden="true"]') === null && element.parentElement.textContent.trim() === '') {
            found.color.push(`${A.describe(element)} es un punto de color sin texto que lo acompañe`);
        }
    }

    return found;
}

/**
 * What can be pressed without being a control: a pointer cursor of its own or an onclick. A
 * container that hands the click to the controls inside it is left out, and so is the
 * backdrop of a dialog, which Escape replaces (A62).
 */
function pressedInPage(dialogSelector) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);
    const kinds = new Map();
    const viewport = document.documentElement.clientWidth * window.innerHeight;

    for (const element of root ? root.querySelectorAll('*') : []) {
        if (!A.visible(element) || A.isControl(element) || element.closest('a[href], button, summary, label, select')) {
            continue;
        }

        const own = getComputedStyle(element).cursor === 'pointer' && getComputedStyle(element.parentElement).cursor !== 'pointer';
        const box = element.getBoundingClientRect();
        const delegates = element.querySelector('a[href], button, input, select, textarea, summary') !== null;
        const backdrop = element.textContent.trim() === '' && (element.getAttribute('aria-hidden') === 'true' || box.width * box.height > viewport * 0.8);

        if ((own || element.hasAttribute('onclick')) && !delegates && !backdrop) {
            kinds.set(`${element.tagName} ${String(element.className).split(/\s+/).slice(0, 4).join(' ')}`, element);
        }
    }

    return [...kinds.values()].slice(0, 30);
}

function keyboardInPage() {
    return { what: window.__audit.describe(this), role: this.getAttribute('role') ?? '', reachable: this.tabIndex >= 0 };
}

function describeInPage() {
    return window.__audit.describe(this);
}

/** Fills what the form of a dialog asks for and presses the button that sends it. */
function fillAndSendInPage(dialogSelector) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);

    if (!root) {
        return 'closed';
    }

    const values = { email: 'prueba@ui-audit.dentissa.test', password: 'Prueba-segura-123', number: '1', date: '2026-07-30', time: '10:00', tel: '+52 744 555 0100', url: 'https://ui-audit.dentissa.test' };

    for (const field of root.querySelectorAll('input:not([type=hidden]):not([type=file]):not([type=checkbox]):not([type=radio]), textarea, select')) {
        if (!A.visible(field) || field.disabled || field.readOnly) {
            continue;
        }

        if (field.tagName === 'SELECT') {
            const option = [...field.options].find((candidate) => candidate.value !== '' && !candidate.disabled);

            if (field.value === '' && option) {
                field.value = option.value;
            }
        } else if (field.value === '') {
            field.value = values[field.type] ?? 'Texto de prueba';
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const buttons = [...root.querySelectorAll('button')].filter((button) => A.visible(button) && !button.disabled);
    const sends = (button) => button.type === 'submit' || [...button.attributes].some((attribute) => /^data-.*-(submit|accept|confirm-accept)$/.test(attribute.name));
    const send = buttons.find(sends);

    if (!send) {
        return 'none';
    }

    send.click();

    return 'sent';
}

function confirmInPage() {
    const A = window.__audit;
    const accept = [...document.querySelectorAll('button')].find((button) => A.visible(button) && [...button.attributes].some((attribute) => /-confirm-accept$/.test(attribute.name)));

    accept?.click();
}

/** What the status regions say right now, wherever they are. */
function announcedInPage() {
    return [...document.querySelectorAll('[role=status], [role=alert], [aria-live]')]
        .filter((region) => {
            for (let node = region; node instanceof Element; node = node.parentElement) {
                const style = getComputedStyle(node);

                if (style.display === 'none' || style.visibility === 'hidden' || node.getAttribute('aria-hidden') === 'true') {
                    return false;
                }
            }

            return true;
        })
        .map((region) => region.textContent.replace(/\s+/g, ' ').trim())
        .filter(Boolean)
        .join(' · ');
}

const summary = (items) => `${items.length}: ${items.slice(0, 4).join('; ')}${items.length > 4 ? ` (y ${items.length - 4} más)` : ''}`;

export async function run(page, entry) {
    const selector = entry.dialogSelector ?? null;
    // The error pages have nothing to save: their frame mounts no status region.
    const found = await page.evaluate(measureInPage, selector, entry.id, !entry.screen.startsWith('error-'));

    // CA35 · names, as Chrome hands them to a screen reader
    const unnamed = (await page.accessibilityTree()).filter((node) => NAMED_ROLES.includes(node.role) && node.name === '' && node.backendNodeId);
    const names = [];

    for (const node of unnamed.slice(0, 12)) {
        const { object } = await page.send('DOM.resolveNode', { backendNodeId: node.backendNodeId }).catch(() => ({ object: null }));

        names.push(object ? `${node.role === 'image' || node.role === 'img' ? 'imagen' : node.role} ${await page.call(object.objectId, describeInPage)}` : node.role);
    }

    if (unnamed.length > names.length) {
        names.push(`y ${unnamed.length - names.length} más`);
    }

    // CA40 · whatever is pressed can be reached and activated with the keyboard
    const keyboard = [];

    for (const handle of await page.handles(pressedInPage, selector)) {
        const element = await page.call(handle, keyboardInPage);
        const keys = (await page.listeners(handle)).some((type) => ['keydown', 'keyup', 'keypress'].includes(type));

        if (!KEYBOARD_ROLES.includes(element.role) || !element.reachable || !keys) {
            keyboard.push(element.what);
        }
    }

    return [
        ['control o imagen sin nombre accesible', names],
        ['campo sin etiqueta visible', found.etiqueta],
        ['campos obligatorios sin distinguir', found.obligatorio],
        ['error sin asociar a su campo', found.error],
        ['región de estado', found.region],
        ['contraste del borde de un control', found.borde],
        ['contraste de un icono de acción', found.icono],
        ['estado solo por color', found.color],
        ['se pulsa pero no se alcanza con el teclado', keyboard],
    ].filter(([, items]) => items.length > 0).map(([rule, items]) => ({ rule, message: summary(items) }));
}

/** CA37 and A63 · what a dialog announces when saving fails, and when it works. */
export async function runFresh(page, entry) {
    if (!entry.dialogSelector) {
        return [];
    }

    const failures = [];
    const words = forbiddenWords();
    let answer = { status: 500, body: { error: 'SQLSTATE[HY000]: General error in backend API endpoint' } };
    let requests = 0;
    const stop = await page.intercept((request) => {
        if (request.method === 'GET') {
            return null;
        }

        requests++;

        return answer;
    });

    try {
        const sent = await page.evaluate(fillAndSendInPage, entry.dialogSelector);

        if (sent !== 'sent') {
            return [];
        }

        await page.settle();

        if (requests === 0) {
            await page.evaluate(confirmInPage);
            await page.settle();
        }

        const failed = await page.evaluate(announcedInPage);

        if (failed === '') {
            failures.push({ rule: 'no se anuncia el error', message: 'tras enviar el formulario sin éxito ninguna región de estado dice nada' });
        } else if (requests > 0) {
            const leaked = [
                ...words.technical.filter((term) => new RegExp(`(?<![\\p{L}\\p{N}])${term}(?![\\p{L}\\p{N}])`, term === term.toUpperCase() ? 'u' : 'iu').test(failed)),
                ...words.spelling.filter((word) => new RegExp(`(?<![\\p{L}\\p{N}])${word}(?![\\p{L}\\p{N}])`, 'iu').test(failed)),
                ...(/SQLSTATE|General error|Internal Server/i.test(failed) ? ['el texto de la API'] : []),
            ];

            if (leaked.length > 0) {
                failures.push({ rule: 'el mensaje anunciado no es propio', message: `"${failed.slice(0, 120)}" contiene: ${leaked.join(', ')}` });
            }
        }

        if (requests === 0) {
            return failures;
        }

        answer = { status: 200, body: { message: 'ok', data: {} } };
        const again = await page.evaluate(fillAndSendInPage, entry.dialogSelector);

        if (again === 'sent') {
            await page.settle();
            await page.evaluate(confirmInPage);
            await page.settle();

            if ((await page.evaluate(announcedInPage)) === '') {
                failures.push({ rule: 'no se anuncia el guardado', message: 'tras guardar ninguna región de estado dice nada' });
            }
        }
    } finally {
        await stop();
    }

    return failures;
}
