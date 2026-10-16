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
const rewrites = new WeakMap();

function region(kind) {
    const selector = kind === 'error' ? '[data-ui-alert]' : '[data-ui-status]';
    const open = [...document.querySelectorAll('dialog[open]')].at(-1);

    return open?.querySelector(selector) ?? document.querySelector('[data-ui-status-region]:not(dialog *) ' + selector) ?? document.querySelector(selector);
}

function show(target, message) {
    clearTimeout(timers.get(target));
    clearTimeout(rewrites.get(target));

    // El mismo mensaje dos veces seguidas no cambia el texto de la región y un lector de pantalla
    // no lo repetiría: se vacía un momento antes de volver a escribirlo.
    if (message !== '' && target.textContent === message) {
        target.textContent = '';
        rewrites.set(target, setTimeout(() => {
            target.textContent = message;
            target.toggleAttribute('data-shown', true);
        }, 50));

        return;
    }

    target.textContent = message;
    target.toggleAttribute('data-shown', message !== '');
}

function write(kind, message) {
    const target = region(kind);

    if (!target) {
        return;
    }

    show(target, message);

    // Se vacía la misma región donde se escribió. Un error dentro de un diálogo abierto no caduca:
    // sigue a la vista hasta el siguiente envío o hasta cerrarlo (spec 016, CA37).
    if (message !== '' && !(kind === 'error' && target.closest('dialog[open]'))) {
        timers.set(target, setTimeout(() => show(target, ''), SHOWN_FOR[kind]));
    }

    if (message !== '' && message !== SENDING) {
        keepFocusWhereItWas();
    }
}

// Al cerrarse un diálogo, sus regiones y sus campos quedan sin errores: al reabrirlo no aparece
// un aviso ni una marca vieja aunque sus campos se rellenen sin escribir (spec 017).
document.addEventListener('close', (event) => {
    if (event.target instanceof HTMLDialogElement) {
        event.target.querySelectorAll('[data-ui-status], [data-ui-alert]').forEach((target) => show(target, ''));
        event.target.querySelectorAll('[aria-invalid="true"]').forEach(unmarkField);
    }
}, true);

/*
 * El botón que envía se deshabilita mientras dura el envío, y el navegador saca el foco de un
 * control deshabilitado. Cuando llega la respuesta, el foco vuelve a ese botón en cuanto se
 * pueda pulsar, para que el anuncio no lo mueva (spec 016, CA37).
 */
let sentFrom = null;

function keepFocusWhereItWas() {
    const control = sentFrom;
    sentFrom = null;

    if (!control || !control.isConnected || control.getClientRects().length === 0) {
        return;
    }

    const restore = () => {
        if (document.activeElement === null || document.activeElement === document.body) {
            control.focus();
        }
    };

    if (!control.disabled) {
        restore();

        return;
    }

    const observer = new MutationObserver(() => {
        if (!control.disabled) {
            observer.disconnect();
            restore();
        }
    });

    observer.observe(control, { attributes: true, attributeFilter: ['disabled'] });
    setTimeout(() => observer.disconnect(), 5000);
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

/*
 * Mientras se envía algo (un formulario o una acción como eliminar), la región dice "Enviando…".
 * Se anuncia desde la petición misma, así que vale para toda pantalla y todo diálogo; lo que
 * valida la página antes de enviar no llega a pedir nada y no lo anuncia (spec 016, CA37).
 */
const SENDING = 'Enviando…';

function sendsData(method) {
    return !['GET', 'HEAD'].includes(String(method ?? 'GET').toUpperCase());
}

// El último control que se pulsó: un formulario puede deshabilitar su botón antes de enviar
// (pide primero la cookie CSRF), y entonces el foco ya no dice quién envía.
let lastPressed = null;

let lastSubmitted = null;

document.addEventListener('click', (event) => {
    lastPressed = event.target.closest?.('button, a[href], input[type=submit]') ?? null;
}, true);

document.addEventListener('submit', (event) => {
    lastSubmitted = event.target;
    lastPressed = event.submitter ?? lastPressed;
}, true);

function sender() {
    const focused = document.activeElement;

    return focused && focused !== document.body ? focused : lastPressed?.isConnected ? lastPressed : null;
}

function announceSending() {
    sentFrom = sender();
    write('error', '');
    write('status', SENDING);
}

/*
 * Cuando el servidor rechaza los datos (422), cada campo que nombra queda marcado con su
 * mensaje, ligado con aria-describedby, dentro del formulario o del diálogo que envió; se
 * desmarca al volver a escribir en él (spec 016, CA37).
 */
export function markFieldErrors(scope, errors) {
    for (const [name, messages] of Object.entries(errors ?? {})) {
        const field = [...scope.querySelectorAll(`[name="${CSS.escape(name)}"]`)].find((candidate) => candidate.getClientRects().length > 0);
        const message = [].concat(messages).find((text) => typeof text === 'string' && text.trim() !== '');

        if (!field || !message) {
            continue;
        }

        let target = document.getElementById(field.dataset.errorTarget ?? '') ?? document.getElementById(`${field.id}-error`);

        if (!target) {
            target = document.createElement('p');
            target.id = `${field.id || name}-error`;
            target.className = 'mt-1 text-sm font-semibold text-danger';
            field.insertAdjacentElement('afterend', target);
        }

        const others = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter((id) => id !== '' && id !== target.id);

        target.textContent = message;
        target.classList.remove('hidden');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', [...others, target.id].join(' '));

        if (field.hasAttribute('data-ui-input')) {
            field.classList.remove('border-field');
            field.classList.add('border-2', 'border-danger');
        }

        field.addEventListener('input', () => unmarkField(field), { once: true });
    }
}

/** Retira la marca de error de un campo: su texto, aria-invalid, su enlace y el borde. */
function unmarkField(field) {
    const target = document.getElementById(field.dataset.errorTarget ?? '') ?? document.getElementById(`${field.id}-error`);
    const others = (field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter((id) => id !== '' && id !== target?.id);

    target?.classList.add('hidden');
    field.removeAttribute('aria-invalid');

    if (field.hasAttribute('data-ui-input')) {
        field.classList.remove('border-2', 'border-danger');
        field.classList.add('border-field');
    }

    if (others.length > 0) {
        field.setAttribute('aria-describedby', others.join(' '));
    } else {
        field.removeAttribute('aria-describedby');
    }
}

async function markRefusedFields(response, scope) {
    if (response.status !== 422 || !scope) {
        return;
    }

    const payload = await response.clone().json().catch(() => null);
    markFieldErrors(scope, payload?.errors);
}

/** Donde están los campos de lo que se envía: el formulario del control que envió, o el diálogo abierto. */
function sendingScope() {
    const from = sender();

    return from?.closest('form') ?? (lastSubmitted?.isConnected ? lastSubmitted : null) ?? [...document.querySelectorAll('dialog[open]')].at(-1) ?? null;
}

const nativeFetch = window.fetch.bind(window);

window.fetch = function (input, init) {
    if (!sendsData(init?.method ?? (input instanceof Request ? input.method : 'GET'))) {
        return nativeFetch(input, init);
    }

    const scope = sendingScope();
    announceSending();

    return nativeFetch(input, init).then((response) => {
        markRefusedFields(response, scope);

        return response;
    });
};

window.axios?.interceptors.request.use((config) => {
    if (sendsData(config.method)) {
        announceSending();
    }

    return config;
});

window.uiStatus = { announce, announceError, announceFailure, clearAnnouncements, failureMessage, markFieldErrors };
