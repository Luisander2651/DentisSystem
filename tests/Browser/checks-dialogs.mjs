/*
 * ui-audit · every dialog, on every screen that mounts it (spec 016).
 *
 *   título            CA12  the name a screen reader gets for the dialog is its title
 *   foco al abrir     CA12  the focus is inside the dialog once it opens
 *   Escape            CA12  Escape closes it; with data typed in, it may first ask (the entry's
 *                           `confirmOnEscape`), and accepting closes it
 *   foco al cerrar    CA12  and the focus goes back to the control that opened it
 *   arrastre          CA12  pressing inside and letting go on the backdrop does not close it
 *   tras guardar      CA12  after saving and reloading its listing, the focus is not lost (runs
 *                           on a visit of its own, `runFresh`, with the API answered from here)
 *   ancho             CA26  it fits in the width of the window (M03)
 *   alto              CA13  it fits in its height, with its actions in reach (M09)
 *   expects           017   what the entry declares about its dialog: texts, controls that must
 *                           not be in sight, the focus on opening and where it goes on closing
 *   flujos            017   with the API answered from here, what each of the entry's `flows`
 *                           sends and how the dialogs, the fields and the notices end up
 *
 * It runs after the steps of a dialog entry, with the dialog open, and leaves it closed.
 */

import { confirmInPage, fillAndSendInPage } from './checks-a11y.mjs';

function measureInPage(dialogSelector, width, height) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);

    if (!root) {
        return null;
    }

    const dialog = root.matches('dialog, [role=dialog], [role=alertdialog]') ? root : root.querySelector('dialog, [role=dialog], [role=alertdialog]');
    const labelledBy = (dialog?.getAttribute('aria-labelledby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));
    const name = (dialog?.getAttribute('aria-label') ?? '').trim() || labelledBy.map((node) => node?.textContent.trim() ?? '').join(' ').trim();
    const title = (dialog?.querySelector('h1, h2, h3')?.textContent ?? '').replace(/\s+/g, ' ').trim();
    // Pressed on the title: a select would open its list, and Escape would close only that.
    const heading = dialog?.querySelector('h1, h2, h3') ?? null;
    const fieldBox = heading && A.visible(heading) ? heading.getBoundingClientRect() : null;

    // The panel: the dialog itself, or the smallest painted box that holds all its controls.
    const controls = [...root.querySelectorAll('button, input:not([type=hidden]), select, textarea, a[href]')].filter(A.visible);
    let panel = root;

    if (root.tagName !== 'DIALOG') {
        for (const candidate of root.querySelectorAll('*')) {
            if (A.visible(candidate) && A.rgba(getComputedStyle(candidate).backgroundColor)[3] > 0.9
                && controls.every((control) => candidate.contains(control)) && panel.contains(candidate)) {
                panel = candidate;
            }
        }
    }

    const box = panel.getBoundingClientRect();
    const buttons = controls.filter((control) => control.tagName === 'BUTTON');
    const last = buttons.at(-1)?.getBoundingClientRect() ?? null;

    return {
        isDialog: dialog !== null,
        name,
        title,
        field: fieldBox ? { x: Math.round(fieldBox.left + fieldBox.width / 2), y: Math.round(fieldBox.top + fieldBox.height / 2) } : null,
        focusInside: root.contains(document.activeElement) && document.activeElement !== root.ownerDocument.body,
        box: { left: Math.round(box.left), right: Math.round(box.right), top: Math.round(box.top), bottom: Math.round(box.bottom) },
        viewport: { width, height },
        lastAction: last ? { what: A.describe(buttons.at(-1)), top: Math.round(last.top), bottom: Math.round(last.bottom) } : null,
    };
}

function afterClosingInPage() {
    const A = window.__audit;
    // Accepting the question of Escape is a click too: the opener was kept before it.
    const opener = A.dialogOpener ?? A.lastClicked;
    const active = document.activeElement;

    return {
        opener: opener ? A.describe(opener) : null,
        openerVisible: opener ? A.visible(opener) : false,
        onOpener: opener !== null && active === opener,
        lost: active === null || active === document.body,
        active: active && active !== document.body ? A.describe(active) : 'el documento',
    };
}

function isOpenInPage(dialogSelector) {
    return window.__audit.scope(dialogSelector) !== null;
}

/**
 * What an entry declares about its own dialog (`expects`, spec 017): the text a control or title
 * shows (`text` + `is`), a control that must not be in sight (`absent`), the control that holds
 * the focus when the dialog opens (`focus`).
 */
function expectationsInPage(expects) {
    const A = window.__audit;
    const shown = (selector) => [...document.querySelectorAll(selector)].find((element) => A.visible(element)) ?? null;
    const failures = [];

    for (const expectation of expects) {
        if (expectation.text !== undefined) {
            const element = shown(expectation.text);
            const text = element ? element.textContent.replace(/\s+/g, ' ').trim() : null;

            if (text !== expectation.is) {
                failures.push({ rule: 'texto distinto del esperado', message: `${expectation.text} dice ${text === null ? 'nada (no está a la vista)' : `"${text}"`} y debía decir "${expectation.is}"` });
            }
        } else if (expectation.absent !== undefined) {
            const element = shown(expectation.absent);

            if (element) {
                failures.push({ rule: 'control que no debía estar', message: `${A.describe(element)} está a la vista (${expectation.absent})` });
            }
        } else if (expectation.focus !== undefined) {
            const active = document.activeElement;

            if (!active || !active.matches(expectation.focus)) {
                failures.push({ rule: 'foco inicial en otro control', message: `debía estar en ${expectation.focus} y está en ${active && active !== document.body ? A.describe(active) : 'el documento'}` });
            }
        }
    }

    return failures;
}

function focusMatchesInPage(selector) {
    const active = document.activeElement;

    return {
        matches: active !== null && active.matches(selector),
        active: active && active !== document.body ? window.__audit.describe(active) : 'el documento',
    };
}

export async function run(page, entry) {
    if (!entry.dialogSelector) {
        return [];
    }

    const open = await page.evaluate(measureInPage, entry.dialogSelector, page.width, page.height);

    if (open === null) {
        return [];
    }

    const failures = [];
    const { box, viewport } = open;
    const expects = entry.expects ?? [];

    if (expects.length > 0) {
        failures.push(...await page.evaluate(expectationsInPage, expects.filter((expectation) => expectation.focusAfterClose === undefined)));
    }

    if (!open.isDialog || open.name === '') {
        failures.push({ rule: 'diálogo sin título como nombre accesible', message: open.isDialog ? 'aria-labelledby no apunta a un título con texto' : 'ningún elemento tiene el papel de diálogo' });
    } else {
        // What a screen reader gets, from the accessibility tree, is the title of the dialog.
        const named = (await page.accessibilityTree()).filter((node) => ['dialog', 'alertdialog'].includes(node.role)).map((node) => node.name.replace(/\s+/g, ' ').trim());

        if (open.title !== '' && !named.includes(open.title)) {
            failures.push({ rule: 'diálogo sin título como nombre accesible', message: `se anuncia como "${named.join('", "') || 'sin nombre'}" y su título es "${open.title}"` });
        }
    }

    if (!open.focusInside) {
        failures.push({ rule: 'el foco no entra al diálogo al abrirlo', message: 'el foco sigue fuera del diálogo' });
    }

    if (box.left < -1 || box.right > viewport.width + 1) {
        failures.push({ rule: 'diálogo más ancho que la ventana', message: `ocupa de x=${box.left} a x=${box.right} px en una ventana de ${viewport.width} px` });
    }

    if (box.top < -1 || box.bottom > viewport.height + 1) {
        failures.push({ rule: 'diálogo más alto que la ventana', message: `ocupa de y=${box.top} a y=${box.bottom} px en una ventana de ${viewport.height} px` });
    }

    if (open.lastAction && (open.lastAction.top < -1 || open.lastAction.bottom > viewport.height + 1)) {
        failures.push({ rule: 'acciones del diálogo fuera de la vista', message: `${open.lastAction.what} queda en y=${open.lastAction.top}–${open.lastAction.bottom} px` });
    }

    // Pressing inside (on its title) and letting go on the backdrop must not close it: that is how
    // a selection that goes too far ends. A dialog that fills the window has no backdrop.
    const outside = [[2, 2], [viewport.width - 2, viewport.height - 2]].find(([x, y]) => x < box.left || x > box.right || y < box.top || y > box.bottom);

    if (open.field && outside) {
        await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', x: open.field.x, y: open.field.y, button: 'left', clickCount: 1 });
        await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: outside[0], y: outside[1], button: 'left' });
        await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: outside[0], y: outside[1], button: 'left', clickCount: 1 });
        await page.settle();

        if (!(await page.evaluate(isOpenInPage, entry.dialogSelector))) {
            failures.push({ rule: 'el diálogo se cierra al arrastrar fuera', message: 'pulsar dentro y soltar sobre el fondo lo cierra' });

            return failures;
        }
    }

    await page.evaluate(() => { window.__audit.dialogOpener = window.__audit.lastClicked; });
    await page.press('Escape');

    if (entry.confirmOnEscape) {
        try {
            await page.waitFor(`(${isOpenInPage})(${JSON.stringify(entry.confirmOnEscape.dialog)})`, { timeout: 1500, what: 'la pregunta al pulsar Escape' });
        } catch {
            failures.push({ rule: 'Escape no pregunta antes de descartar', message: `${entry.confirmOnEscape.dialog} no se abre` });

            return failures;
        }

        await page.click(entry.confirmOnEscape.accept);
    }

    try {
        await page.waitFor(`!(${isOpenInPage})(${JSON.stringify(entry.dialogSelector)})`, { timeout: 1500, what: 'el cierre con Escape' });
    } catch {
        failures.push({ rule: 'Escape no cierra el diálogo', message: `${entry.dialogSelector} sigue abierto` });

        return failures;
    }

    // A dialog opened from another one that closes first (spec 017, "Reprogramar"): the focus goes
    // back to what opened the first one, which the entry names.
    const returnsTo = expects.find((expectation) => expectation.focusAfterClose !== undefined)?.focusAfterClose;

    if (returnsTo !== undefined) {
        const focus = await page.evaluate(focusMatchesInPage, returnsTo);

        if (!focus.matches) {
            failures.push({ rule: 'el foco no vuelve al control que abrió el diálogo', message: `debía volver a ${returnsTo} y está en ${focus.active}` });
        }

        return failures;
    }

    const closed = await page.evaluate(afterClosingInPage);

    if (closed.openerVisible ? !closed.onOpener : closed.lost) {
        failures.push({
            rule: 'el foco no vuelve al control que abrió el diálogo',
            message: closed.openerVisible ? `debía volver a ${closed.opener} y está en ${closed.active}` : 'el foco se pierde al cerrar',
        });
    }

    return failures;
}

/** From now on, keeps every text written to a status region, as checks-a11y does. */
function recordAnnouncementsInPage() {
    const said = [];
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
    window.__flowAnnounced = said;
}

function fillInPage(selector, value) {
    const field = [...document.querySelectorAll(selector)].find(window.__audit.visible);

    if (!field) {
        return false;
    }

    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
}

function flowStateInPage(expect) {
    const A = window.__audit;
    const regions = (root) => [...root.querySelectorAll('[role=status], [role=alert], [aria-live]')].map((region) => region.textContent.replace(/\s+/g, ' ').trim()).join(' · ');

    return {
        open: Object.fromEntries((expect.open ?? []).map((selector) => [selector, A.scope(selector) !== null])),
        closed: Object.fromEntries((expect.closed ?? []).map((selector) => [selector, A.scope(selector) === null])),
        values: Object.fromEntries(Object.keys(expect.values ?? {}).map((selector) => [selector, document.querySelector(selector)?.value ?? null])),
        dialogSays: expect.dialogSays ? regions(A.scope(expect.dialogSays.in) ?? document.createElement('div')) : '',
        announced: (window.__flowAnnounced ?? []).join(' · '),
    };
}

async function reenter(page, entry, baseUrl) {
    await page.goto(baseUrl + entry.path);

    for (const step of entry.steps ?? []) {
        await page.click(step.click);
    }
}

/**
 * Spec 017 (CA1, CA5, CA7) · what a dialog does when it sends, with the API answered from here:
 * each flow of the entry (`flows`) answers its requests with `answer`, runs its actions (`do`)
 * and checks the requests sent (`requests`, exactly those), the dialogs open and closed, the
 * values kept in the fields, the text in the region of a dialog and the notices of the frame.
 */
async function runFlows(page, entry, baseUrl) {
    const failures = [];

    for (const flow of entry.flows ?? []) {
        const sent = [];
        await reenter(page, entry, baseUrl);
        await page.evaluate(recordAnnouncementsInPage);
        const stop = await page.intercept((request) => {
            if (request.method === 'GET') {
                return null;
            }

            sent.push({ method: request.method, url: request.url, body: request.postData ? JSON.parse(request.postData) : {} });

            return flow.answer ?? { status: 200, body: { message: 'ok', data: {} } };
        });

        try {
            for (const action of flow.do) {
                if (action.fill) {
                    if (!(await page.evaluate(fillInPage, action.fill, action.value))) {
                        throw new Error(`${action.fill} no está a la vista`);
                    }
                } else if (action.fillAll) {
                    await page.evaluate(fillAndSendInPage, action.fillAll, false);
                } else if (action.click) {
                    await page.click(action.click);
                } else if (action.press) {
                    await page.press(action.press);
                } else if (action.backdrop) {
                    await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', x: 2, y: 2, button: 'left', clickCount: 1 });
                    await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: 2, y: 2, button: 'left', clickCount: 1 });
                }

                await page.settle();
            }

            const { expect } = flow;
            const state = await page.evaluate(flowStateInPage, expect);
            const fail = (message) => failures.push({ rule: `flujo "${flow.name}"`, message });

            if (expect.requests !== undefined) {
                if (sent.length !== expect.requests.length) {
                    fail(`envió ${sent.length} peticiones (${sent.map((request) => `${request.method} ${JSON.stringify(request.body)}`).join('; ') || 'ninguna'}) y debía enviar ${expect.requests.length}`);
                } else {
                    expect.requests.forEach((wanted, index) => {
                        const request = sent[index];
                        const wrong = [
                            request.method !== wanted.method ? `método ${request.method}` : null,
                            wanted.to && !new URL(request.url).pathname.endsWith(wanted.to) ? `a ${new URL(request.url).pathname}` : null,
                            ...Object.entries(wanted.has ?? {}).filter(([key, value]) => request.body[key] !== value).map(([key]) => `${key} = ${JSON.stringify(request.body[key])}`),
                            ...(wanted.lacks ?? []).filter((key) => key in request.body).map((key) => `lleva ${key}`),
                        ].filter(Boolean);

                        if (wrong.length > 0) {
                            fail(`la petición ${index + 1} no es la esperada: ${wrong.join(', ')} (${JSON.stringify(request.body)})`);
                        }
                    });
                }
            }

            Object.entries(state.open).filter(([, open]) => !open).forEach(([selector]) => fail(`${selector} se cerró y debía seguir abierto`));
            Object.entries(state.closed).filter(([, closed]) => !closed).forEach(([selector]) => fail(`${selector} sigue abierto`));
            Object.entries(expect.values ?? {}).filter(([selector, value]) => state.values[selector] !== value)
                .forEach(([selector, value]) => fail(`${selector} vale ${JSON.stringify(state.values[selector])} y debía conservar ${JSON.stringify(value)}`));

            if (expect.dialogSays && !state.dialogSays.includes(expect.dialogSays.text)) {
                fail(`la región de ${expect.dialogSays.in} dice "${state.dialogSays || 'nada'}" y debía decir "${expect.dialogSays.text}"`);
            }

            if (expect.announced && !state.announced.includes(expect.announced)) {
                fail(`se anunció "${state.announced || 'nada'}" y debía anunciarse "${expect.announced}"`);
            }
        } catch (error) {
            failures.push({ rule: `flujo "${flow.name}"`, message: error.message });
        } finally {
            await stop();
        }
    }

    return failures;
}

/** CA12 · saved, the listing reloads and the control that opened the dialog may be gone. */
export async function runFresh(page, entry, { baseUrl } = {}) {
    if (!entry.flows) {
        return runSavedFocus(page, entry);
    }

    const failures = await runFlows(page, entry, baseUrl);

    if (entry.sends) {
        await reenter(page, entry, baseUrl);
        failures.push(...await runSavedFocus(page, entry));
    }

    return failures;
}

async function runSavedFocus(page, entry) {
    if (!entry.dialogSelector || !entry.sends) {
        return [];
    }

    const stop = await page.intercept((request) => (request.method === 'GET' ? null : { status: 200, body: { message: 'ok', data: {} } }));

    try {
        if ((await page.evaluate(fillAndSendInPage, entry.dialogSelector)) !== 'sent') {
            return [];
        }

        await page.settle();
        await page.evaluate(confirmInPage);
        await page.settle();

        try {
            await page.waitFor(`!(${isOpenInPage})(${JSON.stringify(entry.dialogSelector)})`, { timeout: 3000, what: 'el cierre tras guardar' });
        } catch {
            // A dialog that stays open after saving keeps the focus inside: nothing to check.
            return [];
        }

        const focused = await page.evaluate(() => (document.activeElement && document.activeElement !== document.body ? window.__audit.describe(document.activeElement) : null));

        return focused === null
            ? [{ rule: 'el foco se pierde tras guardar', message: 'al cerrarse el diálogo y recargar el listado, el foco queda en el documento' }]
            : [];
    } finally {
        await stop();
    }
}
