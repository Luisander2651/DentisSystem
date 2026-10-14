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

    const closed = await page.evaluate(afterClosingInPage);

    if (closed.openerVisible ? !closed.onOpener : closed.lost) {
        failures.push({
            rule: 'el foco no vuelve al control que abrió el diálogo',
            message: closed.openerVisible ? `debía volver a ${closed.opener} y está en ${closed.active}` : 'el foco se pierde al cerrar',
        });
    }

    return failures;
}

/** CA12 · saved, the listing reloads and the control that opened the dialog may be gone. */
export async function runFresh(page, entry) {
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
