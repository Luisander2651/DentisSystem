/*
 * Región de estado (docs/design/system.md → Región de estado): escribe un mensaje donde un
 * lector de pantalla lo anuncia sin mover el foco. Con un diálogo abierto, en la región de ese
 * diálogo (el resto del documento queda inerte); si no, en la del marco.
 *
 * Los mensajes de un fallo son nuestros, en español: lo que responde la API no se muestra,
 * salvo los mensajes de validación, que explican qué corregir.
 */
const SHOWN_FOR = { status: 6000, error: 12000 };

const FAILURES = {
    forbidden: 'No tienes permiso para hacer esto.',
    missing: 'No encontramos lo que buscabas. Puede que ya no exista.',
    conflict: 'No se pudo guardar porque choca con otro registro. Revisa los datos e inténtalo de nuevo.',
    busy: 'Hiciste demasiados intentos seguidos. Espera un momento e inténtalo de nuevo.',
    server: 'Algo salió mal de nuestro lado. Inténtalo de nuevo en un momento.',
    offline: 'No hay conexión. Revisa tu red e inténtalo de nuevo.',
    invalid: 'Revisa los datos marcados e inténtalo de nuevo.',
};

const timers = new WeakMap();

function region(kind) {
    const selector = kind === 'error' ? '[data-ui-alert]' : '[data-ui-status]';
    const open = [...document.querySelectorAll('dialog[open], [data-ui-dialog-open]')].at(-1);

    return open?.querySelector(selector) ?? document.querySelector('[data-ui-status-region]:not(dialog *) ' + selector) ?? document.querySelector(selector);
}

function write(kind, message) {
    const target = region(kind);

    if (!target) {
        return;
    }

    clearTimeout(timers.get(target));
    target.textContent = message;
    target.toggleAttribute('data-shown', message !== '');

    if (message !== '') {
        timers.set(target, setTimeout(() => write(kind, ''), SHOWN_FOR[kind]));
    }
}

/** Anuncia que algo terminó bien. */
export function announce(message) {
    write('error', '');
    write('status', message);
}

/** Anuncia un error con un texto propio. */
export function announceError(message) {
    write('status', '');
    write('error', message);
}

/** Retira lo que hubiera en las regiones. */
export function clearAnnouncements() {
    write('status', '');
    write('error', '');
}

/** El primer mensaje de validación de una respuesta 422, o null. */
function validationMessage(payload) {
    const errors = payload?.errors;

    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat().find((message) => typeof message === 'string' && message.trim() !== '');

        if (first) {
            return first;
        }
    }

    return typeof payload?.message === 'string' && payload.message.trim() !== '' ? payload.message : null;
}

/**
 * El mensaje que se muestra por una respuesta fallida de la API. `status` es su código HTTP,
 * o 0 cuando ni siquiera hubo respuesta.
 */
export function failureMessage(status, payload = null) {
    if (status === 422) {
        return validationMessage(payload) ?? FAILURES.invalid;
    }

    if (status === 0) {
        return FAILURES.offline;
    }

    if (status === 401 || status === 403) {
        return FAILURES.forbidden;
    }

    if (status === 404) {
        return FAILURES.missing;
    }

    if (status === 409) {
        return FAILURES.conflict;
    }

    if (status === 429) {
        return FAILURES.busy;
    }

    return FAILURES.server;
}

/** Anuncia el fallo de una petición y devuelve el mensaje mostrado. */
export function announceFailure(status, payload = null) {
    const message = failureMessage(status, payload);
    announceError(message);

    return message;
}

window.uiStatus = { announce, announceError, announceFailure, clearAnnouncements, failureMessage };
