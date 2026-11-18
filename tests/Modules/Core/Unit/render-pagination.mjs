/*
 * Spec 028: runs a scenario of resources/js/ui/pagination.js in Node and prints the result as JSON.
 * PaginationScriptTest calls it:
 *
 *   node render-pagination.mjs <scenario> '<json arguments>'
 *
 * The module builds the control as markup and reads and writes the address as plain strings, so no
 * DOM is needed here.
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

/* ui/status.js listens on the document and wraps fetch when it loads; nothing here uses either. */
globalThis.document = { addEventListener: () => {}, querySelector: () => null, querySelectorAll: () => [] };
globalThis.window = { fetch: () => Promise.resolve(), location: { search: '' } };

const [scenario, rawArguments = '{}'] = process.argv.slice(2);
const args = JSON.parse(rawArguments);
const pagination = await import(new URL('../../../../resources/js/ui/pagination.js', import.meta.url));

const scenarios = {
    window: ({ page, lastPage }) => pagination.pageWindow(page, lastPage),
    markup: ({ meta, noun }) => ({ html: pagination.paginationMarkup(meta, noun) }),
    read: ({ search }) => pagination.readListState(search),
    query: ({ state }) => ({ query: pagination.listStateQuery(state) }),
};

process.stdout.write(JSON.stringify(await scenarios[scenario](args)));
