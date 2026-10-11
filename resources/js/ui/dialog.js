/*
 * Diálogos (docs/design/system.md → Diálogo): abre y cierra un diálogo dejando el foco dentro y
 * devolviéndolo, al cerrar, al control que lo abrió.
 */
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const openers = new WeakMap();

function restoreFocus(dialog) {
    const opener = openers.get(dialog);
    openers.delete(dialog);

    if (opener && opener.isConnected) {
        opener.focus();
    }
}

function firstFocusable(dialog) {
    return [...dialog.querySelectorAll(FOCUSABLE)].find((element) => element.getClientRects().length > 0 && !element.hasAttribute('data-dialog-close')) ?? dialog;
}

/** Abre un diálogo. `opener` es el control al que vuelve el foco; por defecto, el que lo tiene ahora. */
export function openDialog(dialog, opener = document.activeElement) {
    if (!(dialog instanceof HTMLDialogElement) || dialog.open) {
        return;
    }

    openers.set(dialog, opener instanceof HTMLElement && opener !== document.body ? opener : null);

    dialog.showModal();
    firstFocusable(dialog).focus();
}

/** Cierra un diálogo. Quien lo use escucha su evento `close` para limpiar lo suyo. */
export function closeDialog(dialog) {
    if (!(dialog instanceof HTMLDialogElement) || !dialog.open) {
        return;
    }

    dialog.close();
}

export function isDialogOpen(dialog) {
    return dialog instanceof HTMLDialogElement && dialog.open;
}

// Un <dialog> se cierra con su botón de cerrar y al pulsar fuera de él; al cerrarse, por la
// vía que sea (también Escape), devuelve el foco.
document.addEventListener('click', (event) => {
    const dialog = event.target.closest?.('dialog[data-ui-dialog]');

    if (dialog && (event.target === dialog || event.target.closest('[data-dialog-close]'))) {
        closeDialog(dialog);
    }
});

document.addEventListener('close', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        restoreFocus(event.target);
    }
}, true);

window.uiDialog = { open: openDialog, close: closeDialog, isOpen: isDialogOpen };
