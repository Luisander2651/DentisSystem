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

    /* Escape with the folding menu open, closed, and another key. */
    async escape({ open, key }) {
        const { closeMenuOnEscape } = await import(new URL('core.js', site));
        const attributes = new Set(open ? ['open'] : []);
        let focused = false;
        let expanded = open ? 'true' : null;
        const menu = {
            removeAttribute: (name) => attributes.delete(name),
            querySelector: () => ({ focus: () => { focused = true; }, setAttribute: (name, value) => { expanded = value; } }),
        };
        const doc = { querySelector: (selector) => (selector === 'details.menu-m[open]' && attributes.has('open') ? menu : null) };
        const handled = closeMenuOnEscape(doc, { key });

        return { handled, open: attributes.has('open'), focused, expanded };
    },

    /* Remaining time of a promotion, on the clinic's calendar. */
    async promotion({ start, end, at }) {
        const { promotionLeft, promotionBar, clinicDate } = await import(new URL('home.js', site));
        const now = new Date(at);

        return { left: promotionLeft(end, now), bar: promotionBar(start, end, now), today: clinicDate(now) };
    },

    /* A carousel driven by a list of actions. */
    async carousel({ count, actions, still = false }) {
        const { createCarousel } = await import(new URL('home.js', site));
        const carousel = createCarousel(count, () => {}, { still });
        const seen = [];

        for (const [action, argument] of actions) {
            carousel[action](argument);
            seen.push({ after: action, current: carousel.current, moving: carousel.moving });
        }

        return seen;
    },

    /* Where Tab moves the focus inside a dialog. */
    async trap({ focusables, current, backwards }) {
        const { trappedFocus } = await import(new URL('home.js', site));

        return { next: trappedFocus(focusables, current, backwards) };
    },

    /*
     * The 3D tour with or without WebGL and with or without less motion. A fake WebGL context
     * records the colours the shader receives; the tokens come from `tokens`.
     */
    async tooth({ webgl, context = true, reducedMotion, tokens }) {
        const { initTooth } = await import(new URL('tooth3d.js', site));
        const colours = {};
        const gl = new Proxy({
            getShaderParameter: () => true,
            getProgramParameter: () => true,
            getAttribLocation: () => 0,
            getUniformLocation: (_, name) => name,
            uniform3f: (name, ...rgb) => {
                colours[name] = rgb;
            },
        }, { get: (target, key) => target[key] ?? (() => ({})) });
        const inserted = [];
        const tourClasses = classList();
        const stage = {
            querySelector: () => null,
            insertBefore: (element) => inserted.push(element),
        };
        const doc = {
            documentElement: {},
            querySelector: (selector) => (selector === '[data-ref="Tour"]' ? { classList: tourClasses, querySelector: () => stage } : null),
            createElement: () => {
                const element = {
                    attributes: {},
                    removed: false,
                    classList: classList(),
                    setAttribute: (name, value) => {
                        element.attributes[name] = value;
                    },
                    getContext: (type) => (type === 'webgl' && context ? gl : null),
                    remove: () => {
                        element.removed = true;
                    },
                };

                return element;
            },
        };
        const win = {
            ...(webgl ? { WebGLRenderingContext: function WebGLRenderingContext() {} } : {}),
            Dentissa: { reducedMotion: () => Boolean(reducedMotion), setIf: () => {} },
            getComputedStyle: () => ({ getPropertyValue: (token) => tokens[token] ?? '' }),
            performance: { now: () => 0 },
            requestAnimationFrame: () => 0,
        };
        const drawn = initTooth(win, doc);

        return {
            drawn,
            classes: tourClasses.values(),
            canvases: inserted.filter((element) => !element.removed).map((element) => element.className),
            colours,
        };
    },

    /* Section entrances: which blocks get `in`, and which stop being watched. */
    async reveal({ observer, entering }) {
        const { revealOnce } = await import(new URL('core.js', site));
        const blocks = ['a', 'b', 'c'].map((name) => ({ name, classList: classList() }));
        const unobserved = [];
        let callback = null;
        const win = observer
            ? {
                IntersectionObserver: class {
                    constructor(fn) {
                        callback = fn;
                    }

                    observe() {}

                    unobserve(target) {
                        unobserved.push(target.name);
                    }
                },
            }
            : {};

        revealOnce({ querySelectorAll: () => blocks }, win);
        callback?.(blocks.map((block) => ({ target: block, isIntersecting: entering.includes(block.name) })));
        callback?.(blocks.map((block) => ({ target: block, isIntersecting: false })));

        return { shown: blocks.filter((block) => block.classList.contains('in')).map((block) => block.name), unobserved };
    },

    /* The coupon and tickets as the server orders them: none removed nor reordered. */
    async promos({ tickets, at }) {
        const RealDate = Date;
        globalThis.Date = class extends RealDate {
            constructor(...parts) {
                super(...(parts.length ? parts : [at]));
            }

            static now() {
                return new RealDate(at).getTime();
            }
        };

        const { initPromos } = await import(new URL('home.js', site));
        const fields = {};
        const field = (name) => (fields[name] ??= { textContent: '', hidden: false, style: {} });
        const buttons = tickets.map((ticket) => {
            const left = { textContent: '' };

            return {
                hidden: false,
                attributes: ticket,
                left,
                getAttribute: (name) => ticket[name.replace('data-', '')] ?? null,
                querySelector: () => left,
                addEventListener: () => {},
            };
        });
        const root = {
            querySelectorAll: () => buttons,
            querySelector: (selector) => {
                const match = selector.match(/data-pm="(\w+)"/);

                return match ? field(match[1]) : null;
            },
        };

        initPromos(root);
        globalThis.Date = RealDate;

        return {
            title: fields.title.textContent,
            left: fields.left.textContent,
            of: fields.of.textContent,
            tickets: buttons.map((button) => ({ title: button.attributes.title, hidden: button.hidden, left: button.left.textContent })),
        };
    },
};

if (!scenarios[scenario]) {
    console.error(`Unknown scenario: ${scenario}`);
    process.exit(2);
}

process.stdout.write(JSON.stringify(await scenarios[scenario](args)));
