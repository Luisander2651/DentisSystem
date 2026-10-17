/*
 * Runs resources/js/pages/landing/galeria.js against a minimal fake DOM, answering its fetch
 * with the JSON given as the first argument, and prints what the page ended up showing.
 */

import { registerHooks } from 'node:module';

/* Vite resolves the extensionless imports of resources/js; Node needs the ".js". */
registerHooks({
    resolve: (specifier, context, nextResolve) => {
        if (specifier.startsWith('.') && !/\.\w+$/.test(specifier)) {
            return nextResolve(specifier + '.js', context);
        }

        return nextResolve(specifier, context);
    },
});

const payload = process.argv[2];

const element = () => {
    const classes = new Set(['hidden']);

    return {
        innerHTML: '',
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
            toggle: (name, force) => (force ?? !classes.has(name)) ? classes.add(name) : classes.delete(name),
            contains: (name) => classes.has(name),
        },
    };
};

const elements = {
    '[data-gallery-list]': element(),
    '[data-gallery-loading]': element(),
    '[data-gallery-empty]': element(),
};

let onReady = null;

globalThis.window = globalThis;
globalThis.document = {
    addEventListener: (type, listener) => {
        if (type === 'DOMContentLoaded') {
            onReady = listener;
        }
    },
    querySelector: (selector) => elements[selector] ?? null,
    querySelectorAll: () => [],
    getElementById: () => null,
};
let requested = null;

globalThis.fetch = async (url) => (requested = String(url), new Response(payload, {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
}));

let failed = false;

try {
    await import('../../../../resources/js/pages/landing/galeria.js');
    await onReady();
} catch {
    failed = true;
}

process.stdout.write(JSON.stringify({
    requested,
    list: elements['[data-gallery-list]'].innerHTML,
    emptyShown: !elements['[data-gallery-empty]'].classList.contains('hidden'),
    failed,
}));
