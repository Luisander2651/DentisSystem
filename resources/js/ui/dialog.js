/*
 * Diálogos (docs/design/system.md → Diálogo): abre y cierra un diálogo dejando el foco dentro y
 * devolviéndolo, al cerrar, al control que lo abrió.
 *
 * Mientras dura la migración de la spec 016 convive con el marcado anterior (un div que se
 * muestra y se oculta): las funciones "legacy" lo atienden y T073 las retira.
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

function legacyKeydown(event) {
    if (event.key !== 'Escape') {
        return;
    }

    const open = [...document.querySelectorAll('[data-ui-dialog-open]')].at(-1);

    if (open) {
        event.preventDefault();
        closeDialog(open);
    }
}

function openLegacy(dialog) {
    dialog.classList.remove('hidden');
    dialog.setAttribute('data-ui-dialog-open', '');
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', legacyKeydown);

    if (!dialog.hasAttribute('tabindex')) {
        dialog.setAttribute('tabindex', '-1');
    }

    firstFocusable(dialog).focus();
}

function closeLegacy(dialog) {
    dialog.classList.add('hidden');
    dialog.removeAttribute('data-ui-dialog-open');

    if (!document.querySelector('[data-ui-dialog-open]')) {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', legacyKeydown);
    }

    restoreFocus(dialog);
    dialog.dispatchEvent(new Event('close'));
}

/** Abre un diálogo. `opener` es el control al que vuelve el foco; por defecto, el que lo tiene ahora. */
export function openDialog(dialog, opener = document.activeElement) {
    if (!dialog || isDialogOpen(dialog)) {
        return;
    }

    openers.set(dialog, opener instanceof HTMLElement && opener !== document.body ? opener : null);

    if (!(dialog instanceof HTMLDialogElement)) {
        openLegacy(dialog);

        return;
    }

    dialog.showModal();
    firstFocusable(dialog).focus();
}

/** Cierra un diálogo. Quien lo use escucha su evento `close` para limpiar lo suyo. */
export function closeDialog(dialog) {
    if (!dialog || !isDialogOpen(dialog)) {
        return;
    }

    if (!(dialog instanceof HTMLDialogElement)) {
        closeLegacy(dialog);

        return;
    }

    dialog.close();
}

export function isDialogOpen(dialog) {
    return dialog instanceof HTMLDialogElement ? dialog.open : dialog.hasAttribute('data-ui-dialog-open');
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
