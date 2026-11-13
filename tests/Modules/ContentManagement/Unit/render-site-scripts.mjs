/*
 * Spec 026: runs a scenario of the public site scripts (resources/js/site/) in Node against a
 * minimal fake DOM and prints the result as JSON. SiteScriptsTest calls it:
 *
 *   node render-site-scripts.mjs <scenario> '<json arguments>'
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

const [scenario, rawArguments = '{}'] = process.argv.slice(2);
const args = JSON.parse(rawArguments);
const site = new URL('../../../../resources/js/site/', import.meta.url);

/** A class list as the DOM has it. */
function classList(initial = []) {
    const classes = new Set(initial);

    return {
        add: (...names) => names.forEach((name) => classes.add(name)),
        remove: (...names) => names.forEach((name) => classes.delete(name)),
        toggle: (name, force) => ((force ?? !classes.has(name)) ? classes.add(name) : classes.delete(name)),
        contains: (name) => classes.has(name),
        values: () => [...classes],
    };
}

/** A document with only what core.js reads: the schedule holder and the root element. */
function fakeDocument(schedule) {
    const root = { classList: classList() };
    const holder = { getAttribute: () => JSON.stringify(schedule) };

    return {
        readyState: 'complete',
        documentElement: root,
        querySelector: (selector) => (selector === '[data-clinic-schedule]' ? holder : null),
        querySelectorAll: () => [],
        addEventListener: () => {},
    };
}

const scenarios = {
    /* Whether the clinic is open at an instant, given as ISO 8601 with its offset. */
    async clinic({ schedule, at }) {
        const { clinicStatus, parseSchedule } = await import(new URL('core.js', site));

        return clinicStatus(parseSchedule(schedule), new Date(at));
    },

    /* What core.js does to the page on load, with or without reduced motion. */
    async core({ schedule, reducedMotion }) {
        const { initCore } = await import(new URL('core.js', site));
        const doc = fakeDocument(schedule);
        const win = { matchMedia: (query) => ({ matches: Boolean(reducedMotion) && query.includes('reduce') }) };
        const D = initCore(win, doc);

        return {
            rootClasses: doc.documentElement.classList.values(),
            reducedMotion: D.reducedMotion(),
            api: Object.keys(D).sort(),
            todayHours: D.clinic(new Date('2026-11-16T12:00:00-06:00')).todayHours,
        };
    },
};

if (!scenarios[scenario]) {
    console.error(`Unknown scenario: ${scenario}`);
    process.exit(2);
}

process.stdout.write(JSON.stringify(await scenarios[scenario](args)));
