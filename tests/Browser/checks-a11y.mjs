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
 *   envío                CA37  sending a form or an action announces "Enviando…"; a form the
 *                              page stops before sending (two different passwords) does not,
 *                              and ties its error to the field (A82, A83, A91)
 *   errores de campo     CA37  sent empty, every required field is marked; a 422 from the server
 *                              marks the field it names; a field that turns invalid on leaving it
 *                              says so; typical valid values (a phone, an address) are not marked
 *   anuncio nuevo        CA37  what counts is what is announced after sending, not what was there;
 *                              the focus stays where it was, and an error stays while the dialog
 *                              is open (R13, R18, R19)
 *   controles anidados   CA40, CA43  no control inside another control
 *   otra pestaña         CA43  a link that opens another tab says so in its name
 *   menú plegable        CA42  every option of an open folding menu is reached with Tab, and
 *                              Escape closes it and gives the focus back to its button (A92)
 *
 * Sending a form and closing a menu use the screen up, so they run on a visit of their own
 * (`runFresh`) with the API answered from here: nothing reaches the database.
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
    const found = { etiqueta: [], obligatorio: [], error: [], region: [], borde: [], icono: [], color: [], pestana: [], anidado: [] };

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

    // CA40, CA43 · a control inside another control: two tab stops, and the inner one is unnamed
    for (const inner of visible.filter((element) => element.matches('a[href], button, [role=button], input, select, textarea'))) {
        const outer = inner.parentElement?.closest('a[href], button, [role=button]');

        if (outer) {
            found.anidado.push(`${A.describe(inner)} dentro de ${A.describe(outer)}`);
        }
    }

    // CA43 · a link that opens another tab says so
    for (const link of visible.filter((element) => element.matches('a[href][target=_blank]'))) {
        const name = link.getAttribute('aria-label') ?? link.textContent;

        if (!/pestaña|ventana/i.test(name)) {
            found.pestana.push(`${A.describe(link)} "${name.replace(/\s+/g, ' ').trim().slice(0, 40)}"`);
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
                // The icon is painted with its own colour, which may not be the button's (R21).
                const svg = element.querySelector('svg');
                const color = A.rgba(svg ? getComputedStyle(svg).color : style.color);
                const icon = behind.image ? null : A.worstContrast(color, behind.tones);

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

/** Fills what the form of a dialog asks for and, unless told not to, presses the button that sends it. */
export function fillAndSendInPage(dialogSelector, press = true) {
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

    if (!press) {
        return 'filled';
    }

    const buttons = [...root.querySelectorAll('button')].filter((button) => A.visible(button) && !button.disabled);
    const sends = (button) => button.type === 'submit' || [...button.attributes].some((attribute) => /^data-.*-(submit|accept|confirm-accept)$/.test(attribute.name));
    const send = buttons.find(sends);

    if (!send) {
        return 'none';
    }

    send.focus();
    window.__sentFrom = send;
    send.click();

    return 'sent';
}

export function confirmInPage() {
    const A = window.__audit;
    const accept = [...document.querySelectorAll('button')].find((button) => A.visible(button) && [...button.attributes].some((attribute) => /-confirm-accept$/.test(attribute.name)));

    accept?.click();
}

/** From now on, keeps every text written to a status region (an "Enviando…" lasts a moment). */
function recordAnnouncementsInPage() {
    const said = [];
    // A region repainted with the text it already had (the month of a calendar) says nothing new.
    const regions = '[role=status], [role=alert], [aria-live]';
    const previous = new WeakMap();
    const note = () => {
        for (const region of document.querySelectorAll(regions)) {
            const text = region.textContent.replace(/\s+/g, ' ').trim();

            if (text !== '' && previous.get(region) !== text) {
                said.push(text);
            }

            previous.set(region, text);
        }
    };

    for (const region of document.querySelectorAll(regions)) {
        previous.set(region, region.textContent.replace(/\s+/g, ' ').trim());
    }

    new MutationObserver(note).observe(document.body, { subtree: true, childList: true, characterData: true });
    window.__announcedHistory = said;
}

function announcedHistoryInPage() {
    return (window.__announcedHistory ?? []).join(' · ');
}

/** The one form of a screen that sends something, marked so the calls below can reach it. */
function markScreenFormInPage() {
    const A = window.__audit;
    const forms = [...document.querySelectorAll('form')].filter((form) => A.visible(form)
        && form.closest('dialog, [role=dialog], [role=search]') === null
        && form.querySelector('button[type=submit], button:not([type])') !== null
        && [...form.querySelectorAll('input:not([type=hidden]):not([type=search]), textarea, select')].some(A.visible));

    if (forms.length !== 1) {
        return false;
    }

    forms[0].setAttribute('data-ui-audit-form', '');

    return true;
}

/** Two different passwords in a form that asks for one and its confirmation. */
function mismatchPasswordsInPage(selector) {
    const A = window.__audit;
    const form = document.querySelector(selector);
    const passwords = [...form.querySelectorAll('input[type=password]')].filter((field) => A.visible(field) && !field.disabled);

    if (passwords.length !== 2) {
        return null;
    }

    passwords[0].value = 'Prueba-segura-123';
    passwords[1].value = 'Otra-distinta-456';

    for (const field of passwords) {
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    return A.describe(passwords[1]);
}

function confirmationErrorInPage(selector) {
    const A = window.__audit;
    const confirmation = [...(document.querySelector(selector)?.querySelectorAll('input[type=password]') ?? [])].filter(A.visible)[1];

    if (!confirmation) {
        return null;
    }

    const described = (confirmation.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));

    return confirmation.getAttribute('aria-invalid') === 'true' && described.some((node) => node && A.visible(node) && node.textContent.trim() !== '');
}

/** The open folding menu of the screen: its button gets the focus; returns the options inside. */
function openMenuInPage() {
    const A = window.__audit;
    const toggle = [...document.querySelectorAll('button[aria-expanded="true"][aria-controls]')]
        .find((button) => A.visible(button) && button.closest('dialog, [role=dialog]') === null);
    const panel = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;

    if (!panel || !A.visible(panel)) {
        return null;
    }

    toggle.setAttribute('data-ui-audit-toggle', '');
    panel.setAttribute('data-ui-audit-menu', '');
    toggle.focus();

    return [...panel.querySelectorAll('a[href], button:not([disabled]), input:not([type=hidden]), select, textarea')].filter(A.visible).map((option) => A.describe(option));
}

function focusedInMenuInPage() {
    const focused = document.activeElement;

    return focused?.closest('[data-ui-audit-menu]') ? window.__audit.describe(focused) : null;
}

function menuAfterEscapeInPage() {
    const toggle = document.querySelector('[data-ui-audit-toggle]');
    const panel = document.querySelector('[data-ui-audit-menu]');

    return {
        closed: !window.__audit.visible(panel) && toggle.getAttribute('aria-expanded') === 'false',
        focusBack: document.activeElement === toggle,
    };
}

/** CA42 · an open folding menu, with the keyboard only. */
async function checkMenu(page) {
    const options = await page.evaluate(openMenuInPage);

    if (options === null) {
        return [];
    }

    const failures = [];
    const reached = new Set();

    for (let step = 0; step < options.length + 2; step++) {
        await page.press('Tab');
        const focused = await page.evaluate(focusedInMenuInPage);

        if (focused === null) {
            break;
        }

        reached.add(focused);
    }

    const missed = options.filter((option) => !reached.has(option));

    if (missed.length > 0) {
        failures.push({ rule: 'opción del menú que no se alcanza con el tabulador', message: summary(missed) });
    }

    // Tab has left the menu by now: Escape is pressed from inside it, on its first option.
    await page.evaluate(() => document.querySelector('[data-ui-audit-menu]').querySelector('a[href], button:not([disabled])')?.focus());
    await page.press('Escape');
    const after = await page.evaluate(menuAfterEscapeInPage);

    if (!after.closed) {
        failures.push({ rule: 'el menú plegable no se cierra con Escape', message: 'tras pulsar Escape el menú sigue abierto' });
    } else if (!after.focusBack) {
        failures.push({ rule: 'el foco no vuelve al botón del menú', message: 'tras cerrar con Escape el foco no está en el botón que lo abrió' });
    }

    return failures;
}

/** What was announced since the recording started, leaving out "Enviando…". */
function announcedNewInPage() {
    return (window.__announcedHistory ?? []).filter((text) => !/^enviando/i.test(text)).join(' · ');
}

const FIELD_SELECTOR = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]), textarea, select';

/** Empties what can be typed in and presses the button that sends, without filling anything. */
function clearAndSendInPage(selector, fieldSelector) {
    const A = window.__audit;
    const root = A.scope(selector);

    if (!root) {
        return { sent: 'closed', fields: 0 };
    }

    const fields = [...root.querySelectorAll(fieldSelector)].filter((field) => A.visible(field) && !field.disabled && !field.readOnly);

    for (const field of fields) {
        if (field.tagName === 'SELECT') {
            if ([...field.options].some((option) => option.value === '')) {
                field.value = '';
            }
        } else {
            field.value = '';
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const send = [...root.querySelectorAll('button')].filter((button) => A.visible(button))
        .find((button) => button.type === 'submit' || [...button.attributes].some((attribute) => /^data-.*-(submit|accept|confirm-accept)$/.test(attribute.name)));

    if (!send || fields.length === 0) {
        return { sent: 'none', fields: fields.length };
    }

    if (send.disabled) {
        return { sent: 'disabled', fields: fields.length };
    }

    send.click();

    return { sent: 'sent', fields: fields.length };
}

/** Whether a field is marked with an error a screen reader can reach (repeated in each page function). */
function fieldsAfterEmptyInPage(selector, fieldSelector) {
    const A = window.__audit;
    const isMarked = (field) => {
        const described = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));

        return field.getAttribute('aria-invalid') === 'true' && described.some((node) => node && A.visible(node) && node.textContent.trim() !== '');
    };
    const root = A.scope(selector);
    const fields = root ? [...root.querySelectorAll(fieldSelector)].filter(A.visible) : [];
    const required = fields.filter((field) => (field.required || field.getAttribute('aria-required') === 'true') && field.value === '');

    return {
        unmarked: required.filter((field) => !isMarked(field)).map((field) => A.describe(field)),
        marked: fields.filter(isMarked).length,
    };
}

/** The name of the first field that can be typed in, for the 422 the server would answer. */
function firstNamedFieldInPage(selector) {
    const A = window.__audit;
    const root = A.scope(selector);

    return [...(root?.querySelectorAll('input[name]:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]), textarea[name], select[name]') ?? [])]
        .find((field) => A.visible(field) && !field.disabled && !field.readOnly)?.name ?? null;
}

function namedFieldMarkedInPage(selector, name) {
    const A = window.__audit;
    const isMarked = (field) => {
        const described = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));

        return field.getAttribute('aria-invalid') === 'true' && described.some((node) => node && A.visible(node) && node.textContent.trim() !== '');
    };
    const field = [...(A.scope(selector)?.querySelectorAll(`[name="${name}"]`) ?? [])].find(A.visible);

    return field ? isMarked(field) : null;
}

/** Typical valid values of fields that hold more than letters (R12). */
const TYPICAL = { phone_number: '+52 81 1234 5678', street: 'Av. Juárez 123', postal_code: '64000', blood_type: 'O+', name: 'ISO 9001' };

function typicalValuesInPage(selector, typical) {
    const A = window.__audit;
    const root = A.scope(selector);
    const marked = [];

    for (const [name, value] of Object.entries(typical)) {
        for (const field of [...(root?.querySelectorAll(`input[name="${name}"]`) ?? [])].filter((candidate) => A.visible(candidate) && !candidate.readOnly)) {
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new FocusEvent('blur'));
            field.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));

            if (field.getAttribute('aria-invalid') === 'true') {
                marked.push(`${name} "${value}"`);
            }
        }
    }

    return marked;
}

/** A required field left empty on leaving it: returns the text of its error, or null. */
function leaveEmptyFieldInPage(selector) {
    const A = window.__audit;
    const field = [...(A.scope(selector)?.querySelectorAll('[data-ui-input][required]') ?? [])].find((candidate) => A.visible(candidate) && !candidate.readOnly && !candidate.disabled);

    if (!field) {
        return null;
    }

    field.focus();
    field.value = '';
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.blur();

    return document.getElementById(field.dataset.errorTarget ?? '')?.textContent.trim() || null;
}

function focusAfterSendInPage() {
    const from = window.__sentFrom;

    return { same: Boolean(from) && document.activeElement === from, now: document.activeElement && document.activeElement !== document.body ? window.__audit.describe(document.activeElement) : 'el documento' };
}

/** R2 · opens "Editar usuario" for a role that is not known: its warning is seen inside the dialog. */
function openUnknownRoleInPage() {
    const A = window.__audit;
    const edit = [...document.querySelectorAll('[data-user-edit]')].find(A.visible);

    if (!edit) {
        return false;
    }

    edit.setAttribute('data-user-role-id', 'Rol que no existe');
    edit.click();

    return true;
}

function warningInOpenDialogInPage() {
    const dialog = [...document.querySelectorAll('dialog[open]')].at(-1);

    return dialog ? [...dialog.querySelectorAll('[role=status], [role=alert], [aria-live]')].map((region) => region.textContent.trim()).join(' ') : null;
}

/** What was announced after sending, waiting a moment: a repeated message is rewritten after a pause. */
async function newAnnouncement(page) {
    try {
        await page.waitFor(`(${announcedNewInPage})() !== ''`, { timeout: 1000, what: 'un anuncio nuevo' });
    } catch {
        return '';
    }

    return page.evaluate(announcedNewInPage);
}

/** R11, R12 · fields that check themselves as they are typed in and left. */
async function checkOwnFields(page, selector) {
    const failures = [];
    const marked = await page.evaluate(typicalValuesInPage, selector, TYPICAL);

    if (marked.length > 0) {
        failures.push({ rule: 'valor válido marcado como error', message: summary(marked) });
    }

    await page.evaluate(recordAnnouncementsInPage);
    const error = await page.evaluate(leaveEmptyFieldInPage, selector);

    if (error !== null && !(await page.evaluate(announcedHistoryInPage)).includes(error)) {
        failures.push({ rule: 'el error de un campo no se anuncia', message: `al dejar vacío un campo obligatorio y salir de él no se anuncia "${error}"` });
    }

    return failures;
}

/** R3, R4, R5 · sent empty: the page marks the required fields, or the server's 422 marks its field. */
async function checkEmptySend(page, selector, setAnswer, counter) {
    const field = await page.evaluate(firstNamedFieldInPage, selector);
    setAnswer({ status: 422, body: { message: 'Revisa los datos.', errors: field ? { [field]: ['Este dato no es válido.'] } : {} } });
    counter.requests = 0;
    await page.evaluate(recordAnnouncementsInPage);
    const { sent } = await page.evaluate(clearAndSendInPage, selector, FIELD_SELECTOR);

    if (sent !== 'sent') {
        return [];
    }

    await page.settle();
    const failures = [];

    if (counter.requests === 0) {
        const { unmarked, marked } = await page.evaluate(fieldsAfterEmptyInPage, selector, FIELD_SELECTOR);

        if (unmarked.length > 0) {
            failures.push({ rule: 'error sin asociar a su campo', message: `enviado vacío, sin marcar: ${summary(unmarked)}` });
        } else if (marked === 0 && (await page.evaluate(announcedNewInPage)) !== '') {
            failures.push({ rule: 'error sin asociar a su campo', message: 'enviado vacío, se anuncia un error y ningún campo queda marcado' });
        }

        if (/enviando/i.test(await page.evaluate(announcedHistoryInPage))) {
            failures.push({ rule: 'anuncia un envío que no ocurre', message: 'enviado vacío, se anuncia "Enviando…" sin enviar nada' });
        }
    } else if (field && (await page.evaluate(namedFieldMarkedInPage, selector, field)) === false) {
        failures.push({ rule: 'error del servidor sin asociar a su campo', message: `una respuesta 422 sobre "${field}" no marca ese campo` });
    }

    // Sent empty, a form with nothing compulsory may ask to confirm: that question is closed, so
    // what follows works on the dialog of the entry and not under another one.
    if (await page.evaluate((target) => {
        const top = [...document.querySelectorAll('dialog[open]')].at(-1);

        return Boolean(top) && !top.matches(target) && !top.querySelector(target);
    }, selector)) {
        await page.press('Escape');
        await page.settle();
    }

    return failures;
}

/** CA37 · the form of a screen: "Enviando…" when it sends, and none when the page stops it first. */
async function checkScreenForm(page, entry, baseUrl) {
    const failures = [];
    const selector = '[data-ui-audit-form]';
    const counter = { requests: 0 };
    let answer = { status: 500, body: { error: 'SQLSTATE[HY000]: General error' } };
    const stop = await page.intercept((request) => {
        if (request.method === 'GET') {
            return null;
        }

        counter.requests++;

        return answer;
    });

    try {
        if (!(await page.evaluate(markScreenFormInPage))) {
            return entry.sends ? [{ rule: 'no llega a enviar', message: 'la entrada envía algo y no hay un único formulario que enviar' }] : [];
        }

        failures.push(...await checkOwnFields(page, selector));

        // A82, A91 · two different passwords: stopped by the page, the error is on the field; sent,
        // the server's 422 marks it. Either way the field ends marked (R6, R17).
        const confirmation = (entry.steps ?? []).length === 0 && await page.evaluate(fillAndSendInPage, selector, false) === 'filled'
            ? await page.evaluate(mismatchPasswordsInPage, selector)
            : null;

        if (confirmation !== null) {
            const confirmName = await page.evaluate((target) => [...document.querySelector(target).querySelectorAll('input[type=password]')].filter((field) => !field.disabled)[1]?.name ?? null, selector);
            answer = { status: 422, body: { message: 'Revisa los datos.', errors: confirmName ? { [confirmName]: ['Las contraseñas no coinciden.'] } : {} } };
            counter.requests = 0;
            await page.evaluate(recordAnnouncementsInPage);
            await page.evaluate((target) => document.querySelector(target).requestSubmit(), selector);
            await page.settle();

            if (await page.evaluate(confirmationErrorInPage, selector) === false) {
                failures.push({ rule: 'error sin asociar a su campo', message: `contraseñas distintas: ${confirmation} no queda marcado con el error` });
            } else if (counter.requests === 0) {
                // Typing again, still different: the error stays (R6).
                await page.evaluate((target) => {
                    const field = [...document.querySelector(target).querySelectorAll('input[type=password]')].filter((candidate) => !candidate.disabled)[1];
                    field.value += 'x';
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }, selector);

                if (await page.evaluate(confirmationErrorInPage, selector) === false) {
                    failures.push({ rule: 'el error se borra al escribir', message: `contraseñas aún distintas: ${confirmation} pierde su error al teclear` });
                }
            }

            if (counter.requests === 0 && /enviando/i.test(await page.evaluate(announcedHistoryInPage))) {
                failures.push({ rule: 'anuncia un envío que no ocurre', message: 'con las contraseñas distintas se anuncia "Enviando…" sin enviar nada' });
            }

            await page.goto(baseUrl + entry.path);
            await page.evaluate(markScreenFormInPage);
        }

        // R5 · a 422 from the server marks the field it names
        const field = await page.evaluate(firstNamedFieldInPage, selector);

        if (field) {
            answer = { status: 422, body: { message: 'Revisa los datos.', errors: { [field]: ['Este dato no es válido.'] } } };
            counter.requests = 0;

            if (await page.evaluate(fillAndSendInPage, selector) === 'sent') {
                await page.settle();

                if (counter.requests > 0 && (await page.evaluate(namedFieldMarkedInPage, selector, field)) === false) {
                    failures.push({ rule: 'error del servidor sin asociar a su campo', message: `una respuesta 422 sobre "${field}" no marca ese campo` });
                }
            }

            await page.goto(baseUrl + entry.path);

            for (const step of entry.steps ?? []) {
                if (step.click) {
                    await page.click(step.click);
                }
            }

            await page.evaluate(markScreenFormInPage);
        }

        answer = { status: 500, body: { error: 'SQLSTATE[HY000]: General error' } };
        counter.requests = 0;
        await page.evaluate(recordAnnouncementsInPage);
        const sent = await page.evaluate(fillAndSendInPage, selector);

        if (sent === 'sent') {
            await page.settle();

            if (counter.requests > 0 && !/enviando/i.test(await page.evaluate(announcedHistoryInPage))) {
                failures.push({ rule: 'no se anuncia el envío', message: 'al enviar el formulario ninguna región de estado dice "Enviando…"' });
            }
        }

        // R20 · an entry that sends and did not: the check would pass without having run
        if (entry.sends && (sent !== 'sent' || counter.requests === 0)) {
            failures.push({ rule: 'no llega a enviar', message: sent === 'sent' ? 'el formulario se envió y no salió ninguna petición' : 'no hay botón que lo envíe' });
        }
    } finally {
        await stop();
    }

    return failures;
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
        ['enlace a otra pestaña sin aviso', found.pestana],
        ['control dentro de otro control', found.anidado],
        ['se pulsa pero no se alcanza con el teclado', keyboard],
    ].filter(([, items]) => items.length > 0).map(([rule, items]) => ({ rule, message: summary(items) }));
}

/** CA37 and A63 · what a dialog announces when sending, when saving fails and when it works. */
export async function runFresh(page, entry, { baseUrl }) {
    if (!entry.dialogSelector) {
        return [...await checkMenu(page), ...await checkScreenForm(page, entry, baseUrl)];
    }

    const failures = [];
    const words = forbiddenWords();
    const counter = { requests: 0 };
    let answer = { status: 500, body: { error: 'SQLSTATE[HY000]: General error in backend API endpoint' } };
    const stop = await page.intercept((request) => {
        if (request.method === 'GET') {
            return null;
        }

        counter.requests++;

        return answer;
    });

    try {
        failures.push(...await checkOwnFields(page, entry.dialogSelector));
        failures.push(...await checkEmptySend(page, entry.dialogSelector, (next) => { answer = next; }, counter));

        if (!(await page.evaluate(isOpenInPage, entry.dialogSelector))) {
            return failures;
        }

        // R5 · filled in and refused by the server, the field its 422 names is marked
        const field = await page.evaluate(firstNamedFieldInPage, entry.dialogSelector);

        if (field) {
            answer = { status: 422, body: { message: 'Revisa los datos.', errors: { [field]: ['Este dato no es válido.'] } } };
            counter.requests = 0;

            if (await page.evaluate(fillAndSendInPage, entry.dialogSelector) === 'sent') {
                await page.settle();

                if (counter.requests > 0 && (await page.evaluate(namedFieldMarkedInPage, entry.dialogSelector, field)) === false) {
                    failures.push({ rule: 'error del servidor sin asociar a su campo', message: `una respuesta 422 sobre "${field}" no marca ese campo` });
                }
            }

            if (!(await page.evaluate(isOpenInPage, entry.dialogSelector))) {
                return failures;
            }
        }

        answer = { status: 500, body: { error: 'SQLSTATE[HY000]: General error in backend API endpoint' } };
        counter.requests = 0;
        await page.evaluate(recordAnnouncementsInPage);
        const sent = await page.evaluate(fillAndSendInPage, entry.dialogSelector);

        if (sent !== 'sent') {
            return entry.sends ? [...failures, { rule: 'no llega a enviar', message: 'el diálogo envía algo y no tiene un botón que lo envíe' }] : failures;
        }

        await page.settle();

        if (counter.requests === 0) {
            await page.evaluate(confirmInPage);
            await page.settle();
        }

        if (entry.sends && counter.requests === 0) {
            failures.push({ rule: 'no llega a enviar', message: 'se pulsó enviar y no salió ninguna petición' });
        }

        if (counter.requests > 0 && !/enviando/i.test(await page.evaluate(announcedHistoryInPage))) {
            failures.push({ rule: 'no se anuncia el envío', message: 'al enviar ninguna región de estado dice "Enviando…"' });
        }

        // R18 · only what was announced after sending counts
        const failed = await newAnnouncement(page);

        if (failed === '') {
            failures.push({ rule: 'no se anuncia el error', message: 'tras enviar el formulario sin éxito ninguna región de estado dice nada nuevo' });
        } else if (counter.requests > 0) {
            const leaked = [
                ...words.technical.filter((term) => new RegExp(`(?<![\\p{L}\\p{N}])${term}(?![\\p{L}\\p{N}])`, term === term.toUpperCase() ? 'u' : 'iu').test(failed)),
                ...words.spelling.filter((word) => new RegExp(`(?<![\\p{L}\\p{N}])${word}(?![\\p{L}\\p{N}])`, 'iu').test(failed)),
                ...(/SQLSTATE|General error|Internal Server/i.test(failed) ? ['el texto de la API'] : []),
            ];

            if (leaked.length > 0) {
                failures.push({ rule: 'el mensaje anunciado no es propio', message: `"${failed.slice(0, 120)}" contiene: ${leaked.join(', ')}` });
            }
        }

        // R19 · the message is announced without moving the focus away from where it was
        if (counter.requests > 0 && await page.evaluate(isOpenInPage, entry.dialogSelector)) {
            const focus = await page.evaluate(focusAfterSendInPage);

            if (!focus.same) {
                failures.push({ rule: 'el anuncio mueve el foco', message: `tras el error el foco está en ${focus.now}` });
            }
        }

        // R13 · an error of an open dialog stays until it is sent again or closed
        if (entry.id === 'pacientes-crear' && failed !== '') {
            await new Promise((done) => setTimeout(done, 12500));

            if (!(await page.evaluate(announcedInPage)).includes(failed.split(' · ').at(-1))) {
                failures.push({ rule: 'el error desaparece solo', message: 'el error de guardado deja de verse con el diálogo aún abierto' });
            }
        }

        if (counter.requests === 0) {
            return failures;
        }

        answer = { status: 200, body: { message: 'ok', data: {} } };
        await page.evaluate(recordAnnouncementsInPage);
        const again = await page.evaluate(fillAndSendInPage, entry.dialogSelector);

        if (again === 'sent') {
            await page.settle();
            await page.evaluate(confirmInPage);
            await page.settle();

            if ((await newAnnouncement(page)) === '') {
                failures.push({ rule: 'no se anuncia el guardado', message: 'tras guardar ninguna región de estado dice nada nuevo' });
            }
        }

        // R2 · a role that is not known is warned about inside the dialog, where it is seen
        if (entry.dialog === 'editar-usuario') {
            if (await page.evaluate(isOpenInPage, entry.dialogSelector)) {
                await page.press('Escape');
                await page.settle();
            }

            if (await page.evaluate(openUnknownRoleInPage)) {
                await page.settle();

                if (!/rol/i.test(await page.evaluate(warningInOpenDialogInPage) ?? '')) {
                    failures.push({ rule: 'aviso fuera del diálogo', message: 'con un rol que no se reconoce, el diálogo abierto no muestra el aviso' });
                }
            }
        }
    } finally {
        await stop();
    }

    return failures;
}

function isOpenInPage(dialogSelector) {
    return window.__audit.scope(dialogSelector) !== null;
}
