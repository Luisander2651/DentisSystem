/*
 * Diálogos (docs/design/system.md → Diálogo): abre y cierra un diálogo dejando el foco dentro y
 * devolviéndolo, al cerrar, al control que lo abrió.
 */
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const openers = new WeakMap();

const RERENDER_WINDOW = 5000;

/** Lo que identifica a un control de un listado (`data-patient-edit data-patient-id="…"`), para volver a encontrarlo. */
function selectorOf(control) {
    const hooks = [...control.attributes].filter((attribute) => attribute.name.startsWith('data-'));

    return hooks.length === 0 ? null : control.tagName.toLowerCase() + hooks.map((attribute) => `[${attribute.name}="${CSS.escape(attribute.value)}"]`).join('');
}

/** El primer control que responde a `selector`, a la vista y alcanzable (un listado puede estar repetido y oculto). */
function findShown(selector) {
    // Con un diálogo abierto, lo de fuera está inerte y no puede tomar el foco.
    const open = [...document.querySelectorAll('dialog[open]')].at(-1);

    return selector ? [...document.querySelectorAll(selector)].find((element) => element.getClientRects().length > 0 && (!open || open.contains(element))) ?? null : null;
}

/**
 * Adonde va el foco cuando el control ya no existe: dentro del diálogo que siga abierto (lo
 * demás está inerte) o, si no hay ninguno, al título de la página.
 */
function pageTitle() {
    const open = [...document.querySelectorAll('dialog[open]')].at(-1);

    if (open) {
        const target = firstFocusable(open);

        // Mientras su listado se vuelve a pintar puede no tener controles: el foco queda en él.
        if (target === open && !open.hasAttribute('tabindex')) {
            open.setAttribute('tabindex', '-1');
        }

        return target;
    }

    const title = document.querySelector('main h1, h1');

    if (title && !title.hasAttribute('tabindex')) {
        title.setAttribute('tabindex', '-1');
    }

    return title;
}

/*
 * Al guardar, muchas páginas vuelven a pintar su listado: el control que abrió el diálogo se
 * sustituye por uno igual, o desaparece si se eliminó el registro. El foco lo sigue hasta el nuevo
 * o va al título de la página, en vez de caer al documento (spec 016, CA12).
 */
function followRerender(control) {
    const selector = selectorOf(control);
    const deadline = Date.now() + RERENDER_WINDOW;
    const observer = new MutationObserver(() => {
        if (Date.now() > deadline) {
            observer.disconnect();

            return;
        }

        if (control.isConnected) {
            return;
        }

        observer.disconnect();

        // Si alguien ya movió el foco a otro sitio, se queda allí.
        if (document.activeElement && document.activeElement !== document.body) {
            return;
        }

        focusAndFollow(findShown(selector) ?? pageTitle());
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

/** Lleva el foco a `target` y lo sigue si su listado se vuelve a pintar. */
function focusAndFollow(target) {
    if (!target) {
        return;
    }

    target.focus();
    followRerender(target);
}

function restoreFocus(dialog) {
    const opener = openers.get(dialog);
    openers.delete(dialog);

    if (opener && opener.isConnected && opener.getClientRects().length > 0) {
        focusAndFollow(opener);
    } else if (opener) {
        focusAndFollow(findShown(selectorOf(opener)) ?? pageTitle());
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
// vía que sea (también Escape), devuelve el foco. Pulsar dentro y soltar fuera (una selección
// que se pasa) no cuenta como pulsar fuera: se cerraría con lo escrito.
let pressedOn = null;

document.addEventListener('pointerdown', (event) => {
    pressedOn = event.target;
}, true);

/** Si un clic fue sobre el fondo de `dialog`: pulsado y soltado fuera de su caja. */
export function isBackdropClick(event, dialog) {
    return event.target === dialog && pressedOn === dialog;
}

document.addEventListener('click', (event) => {
    const dialog = event.target.closest?.('dialog[data-ui-dialog]');

    if (dialog && (isBackdropClick(event, dialog) || event.target.closest('[data-dialog-close]'))) {
        closeDialog(dialog);
    }
});

document.addEventListener('close', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        restoreFocus(event.target);
    }
}, true);

window.uiDialog = { open: openDialog, close: closeDialog, isOpen: isDialogOpen, isBackdropClick };
