/*
 * ui-audit · every dialog, on every screen that mounts it (spec 016).
 *
 *   título            CA12  the dialog takes its title as its accessible name
 *   foco al abrir     CA12  the focus is inside the dialog once it opens
 *   Escape            CA12  Escape closes it
 *   foco al cerrar    CA12  and the focus goes back to the control that opened it
 *   ancho             CA26  it fits in the width of the window (M03)
 *   alto              CA13  it fits in its height, with its actions in reach (M09)
 *
 * It runs after the steps of a dialog entry, with the dialog open, and leaves it closed.
 */

function measureInPage(dialogSelector, width, height) {
    const A = window.__audit;
    const root = A.scope(dialogSelector);

    if (!root) {
        return null;
    }

    const dialog = root.matches('dialog, [role=dialog], [role=alertdialog]') ? root : root.querySelector('dialog, [role=dialog], [role=alertdialog]');
    const labelledBy = (dialog?.getAttribute('aria-labelledby') ?? '').split(/\s+/).filter(Boolean).map((id) => document.getElementById(id));
    const name = (dialog?.getAttribute('aria-label') ?? '').trim() || labelledBy.map((node) => node?.textContent.trim() ?? '').join(' ').trim();

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
        focusInside: root.contains(document.activeElement) && document.activeElement !== root.ownerDocument.body,
        box: { left: Math.round(box.left), right: Math.round(box.right), top: Math.round(box.top), bottom: Math.round(box.bottom) },
        viewport: { width, height },
        lastAction: last ? { what: A.describe(buttons.at(-1)), top: Math.round(last.top), bottom: Math.round(last.bottom) } : null,
    };
}

function afterClosingInPage() {
    const A = window.__audit;
    const opener = A.lastClicked;
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

    await page.press('Escape');

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
