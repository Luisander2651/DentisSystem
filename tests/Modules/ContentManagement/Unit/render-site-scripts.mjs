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
        /* A page entry imports its stylesheet for Vite; here it is an empty module. */
        if (specifier.endsWith('.css')) {
            return { url: 'data:text/javascript,', shortCircuit: true };
        }

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

    /* The pinned tour of the gallery: its progress, the turn of each photo and when it pins (T019). */
    async galleryTour({ reduced, positions = [], tilts = [] }) {
        const { tourProgress, frameTilt, initTour } = await import(new URL('gallery.js', site));
        const section = { classList: classList(), style: { values: {}, setProperty(key, value) { this.values[key] = value; } } };
        const view = { scrollLeft: 120, clientWidth: 1000, attributes: { tabindex: '0' }, removeAttribute(key) { delete this.attributes[key]; } };
        const track = { scrollWidth: 3000, children: [], style: section.style };
        const pinned = { offsetHeight: 720 };
        section.querySelector = (selector) => ({ '.hz-sticky': pinned, '.hz-view': view, '.hz-track': track })[selector] ?? null;
        section.getBoundingClientRect = () => ({ top: 92, bottom: 2812, height: 2720 });
        const win = {
            innerHeight: 900,
            innerWidth: 1440,
            matchMedia: () => ({ matches: reduced }),
            getComputedStyle: () => ({ top: '92px' }),
            requestAnimationFrame: (fn) => fn(),
            addEventListener: () => {},
        };
        const pinnedTour = initTour(section, win);

        return {
            pinned: pinnedTour,
            scrub: section.classList.contains('is-scrub'),
            height: section.style.values['--hz-height'] ?? null,
            focusable: 'tabindex' in view.attributes,
            progress: positions.map((sectionTop) => tourProgress({ pinTop: 92, sectionTop, sectionHeight: 2720, pinnedHeight: 720 })),
            tilts: tilts.map((centre) => frameTilt(centre, 1440)),
        };
    },

    /* The photos of the gallery tour that load only when they come near the view (T019). */
    async galleryPhotos({ observer, entering }) {
        const { loadPhotosNearView } = await import(new URL('gallery-photos.js', site));
        const photos = ['a', 'b', 'c'].map((name) => {
            const attributes = { 'data-src': `https://imagenes.example.com/${name}.webp`, src: 'data:image/gif;base64,R0lGOD' };

            return {
                name,
                getAttribute: (key) => attributes[key] ?? null,
                setAttribute: (key, value) => { attributes[key] = value; },
                removeAttribute: (key) => { delete attributes[key]; },
                attributes,
            };
        });
        const view = { querySelectorAll: (selector) => (selector === 'img[data-src]' ? photos : []) };
        const unobserved = [];
        let callback = null;
        let options = null;
        const win = observer
            ? {
                IntersectionObserver: class {
                    constructor(fn, settings) {
                        callback = fn;
                        options = settings;
                    }

                    observe() {}

                    unobserve(target) {
                        unobserved.push(target.name);
                    }
                },
            }
            : {};

        loadPhotosNearView(view, win);
        callback?.(photos.map((photo) => ({ target: photo, isIntersecting: entering.includes(photo.name) })));

        return {
            loaded: photos.filter((photo) => photo.attributes.src.startsWith('https://')).map((photo) => photo.name),
            waiting: photos.filter((photo) => 'data-src' in photo.attributes).map((photo) => photo.name),
            unobserved,
            rootIsTheView: options ? options.root === view : null,
        };
    },

    /* The before-and-after comparator with its flat drawing, driven by a list of actions (T031). */
    async galleryCompare({ reduced = false, actions = [], summaries = {}, frames = [] }) {
        const { initCompare } = await import(new URL('gallery.js', site));
        const tokens = { '--color-enamel': ' #f2ebde', '--color-enamel-white': ' #fefeff', '--color-enamel-stain': ' #d9bd85' };
        const element = (attributes = {}) => {
            const node = {
                attributes: { ...attributes },
                classList: classList(attributes.class ? attributes.class.split(' ') : []),
                style: {},
                hidden: false,
                textContent: attributes.text ?? '',
                children: [],
                markupWrites: 0,
                listeners: {},
                value: attributes.value,
                getAttribute: (key) => node.attributes[key] ?? null,
                setAttribute: (key, value) => { node.attributes[key] = String(value); },
                addEventListener: (type, fn) => { (node.listeners[type] ??= []).push(fn); },
                appendChild: (child) => node.children.push(child),
                replaceChildren: () => { node.children = []; },
                fire: (type) => (node.listeners[type] ?? []).forEach((fn) => fn({ target: node })),
            };
            Object.defineProperty(node, 'innerHTML', { set: () => { node.markupWrites += 1; } });

            return node;
        };
        const names = { white: 'Blanqueamiento Dental', braces: 'Ortodoncia', implant: 'Implantes Dentales' };
        const buttons = ['white', 'braces', 'implant'].map((mode, index) => element({
            'data-ba': mode,
            'aria-pressed': index === 0 ? 'true' : 'false',
            'data-ba-summary': summaries[mode] ?? `Resumen ${mode}`,
            text: ` ${names[mode]} `,
        }));
        const parts = {
            '[data-teeth="before"]': element(),
            '[data-teeth="after"]': element(),
            '[data-ba-after]': element(),
            '.ba-handle': element(),
            '.ba-range': element({ value: '50' }),
            '[data-ba-title]': element({ text: 'Blanqueamiento Dental' }),
            '[data-ba-text]': element({ text: 'Resumen white' }),
            '[data-if="isWhite"]': element(),
        };
        const root = {
            querySelector: (selector) => parts[selector] ?? null,
            querySelectorAll: (selector) => (selector === '[data-ba]' ? buttons : []),
        };
        const doc = { documentElement: {}, createElement: () => element() };
        let observed = null;
        const queued = [];
        const win = {
            matchMedia: (query) => ({ matches: Boolean(reduced) && query.includes('reduce') }),
            getComputedStyle: () => ({ getPropertyValue: (token) => tokens[token] ?? '' }),
            requestAnimationFrame: (fn) => queued.push(fn),
            performance: { now: () => 0 },
            IntersectionObserver: class {
                constructor(fn) { observed = fn; }

                observe() {}

                disconnect() {}
            },
        };
        const teeth = (side) => parts[`[data-teeth="${side}"]`].children.map((tooth) => tooth.style);
        const drawing = (side) => ({
            count: teeth(side).length,
            colours: [...new Set(teeth(side).map((tooth) => tooth.background))],
            turned: teeth(side).filter((tooth) => !/rotate\(0deg\)/.test(tooth.transform)).length,
            missing: teeth(side).filter((tooth) => tooth.opacity === '0').length,
        });
        const snapshot = (after) => ({
            after,
            pressed: buttons.map((button) => button.attributes['aria-pressed']),
            on: buttons.map((button) => button.classList.contains('on')),
            title: parts['[data-ba-title]'].textContent,
            text: parts['[data-ba-text]'].textContent,
            textMarkupWrites: parts['[data-ba-text]'].markupWrites + parts['[data-ba-title]'].markupWrites,
            value: parts['.ba-range'].value,
            valueNow: parts['.ba-range'].attributes['aria-valuenow'] ?? null,
            clip: parts['[data-ba-after]'].style.clipPath ?? null,
            handle: parts['.ba-handle'].style.left ?? null,
            sparkles: !parts['[data-if="isWhite"]'].hidden,
            before: drawing('before'),
            later: drawing('after'),
        });

        initCompare(root, win, doc);
        const seen = [snapshot('start')];

        for (const [action, argument] of actions) {
            if (action === 'input') {
                parts['.ba-range'].value = String(argument);
                parts['.ba-range'].fire('input');
            } else if (action === 'click') {
                buttons.find((button) => button.attributes['data-ba'] === argument).fire('click');
            }

            seen.push(snapshot(`${action} ${argument}`));
        }

        const sweep = [];

        if (observed) {
            observed([{ isIntersecting: true }]);

            for (const time of frames) {
                if (time === 'touch') {
                    parts['.ba-range'].value = '50';
                    parts['.ba-range'].fire('input');
                    continue;
                }

                const step = queued.shift();
                step?.(time);
                sweep.push(parts['.ba-range'].value);
            }
        }

        return { seen, observing: observed !== null, sweep, pendingFrames: queued.length };
    },

    /* The 3D denture of the before and after over a fake WebGL, frame by frame (T032). */
    async galleryDenture({ webgl = 'ok', reduced = false, frames = [], state = { mode: 'white', pos: 50 }, tokens = {}, visible = true }) {
        const { readFileSync } = await import('node:fs');
        const { initDenture, DENTURE_TOKENS } = await import(new URL('gallery.js', site));
        const sheet = readFileSync(new URL('../../css/app.css', site), 'utf8');
        const declared = Object.fromEntries([...sheet.matchAll(/(--color-[\w-]+):\s*(#[0-9a-fA-F]{3,6})\s*;/g)].map((match) => [match[1], ` ${match[2]}`]));
        const values = { ...declared, ...tokens };
        const calls = [];
        let draws = 0;
        let contextOptions = null;
        const gl = {
            VERTEX_SHADER: 1, FRAGMENT_SHADER: 2, COMPILE_STATUS: 3, LINK_STATUS: 4, ARRAY_BUFFER: 5, STATIC_DRAW: 6, FLOAT: 7, COLOR_BUFFER_BIT: 8, TRIANGLES: 9,
            createShader: (type) => ({ type }),
            shaderSource: (shader, source) => { shader.source = source; },
            compileShader: () => {},
            getShaderParameter: () => webgl !== 'broken',
            getShaderInfoLog: () => 'broken shader',
            createProgram: () => ({}),
            attachShader: () => {},
            linkProgram: () => {},
            getProgramParameter: () => true,
            useProgram: () => {},
            createBuffer: () => ({}),
            bindBuffer: () => {},
            bufferData: () => {},
            getAttribLocation: () => 0,
            enableVertexAttribArray: () => {},
            vertexAttribPointer: () => {},
            getUniformLocation: (_program, name) => name,
            viewport: () => {},
            clearColor: () => {},
            clear: () => {},
            uniform1f: (name, x) => calls.push([name, [x]]),
            uniform2f: (name, x, y) => calls.push([name, [x, y]]),
            uniform3f: (name, x, y, z) => calls.push([name, [x, y, z]]),
            drawArrays: () => { draws += 1; },
            RENDERER: 10,
            getExtension: (name) => (name === 'WEBGL_debug_renderer_info' ? { UNMASKED_RENDERER_WEBGL: 11 } : null),
            getParameter: (key) => (key === 11 ? (webgl === 'swiftshader' ? 'ANGLE (Google, Vulkan 1.3.0 (SwiftShader Device (Subzero)), SwiftShader driver)' : 'ANGLE (NVIDIA, GeForce)') : null),
        };
        const canvas = {
            classList: classList(),
            clientWidth: 900,
            clientHeight: 560,
            width: 300,
            height: 150,
            getContext: (kind, options) => {
                contextOptions = options ?? null;

                return kind === 'webgl' && webgl !== 'none' && !(webgl === 'software' && options?.failIfMajorPerformanceCaveat) ? gl : null;
            },
            getBoundingClientRect: () => (visible ? { top: 100, bottom: 660 } : { top: 2000, bottom: 2560 }),
        };
        const flat = { classList: classList() };
        const root = { querySelector: (selector) => ({ '.ba-cv': canvas, '.ba-2d': flat })[selector] ?? null };
        const queued = [];
        const win = {
            innerHeight: 900,
            devicePixelRatio: 2,
            matchMedia: (query) => ({ matches: Boolean(reduced) && query.includes('reduce') }),
            getComputedStyle: () => ({ getPropertyValue: (token) => values[token] ?? '' }),
            requestAnimationFrame: (fn) => queued.push(fn),
        };

        if (webgl !== 'none') {
            win.WebGLRenderingContext = function WebGLRenderingContext() {};
        }

        const started = initDenture(root, state, win, { documentElement: {} });
        const drawn = [];

        for (const time of frames) {
            const step = queued.shift();
            const before = draws;
            const from = calls.length;
            step?.(time);
            const last = (name) => calls.slice(from).filter(([uniform]) => uniform === name).at(-1)?.[1] ?? null;
            drawn.push({ time, drew: draws > before, rot: last('uRot'), split: last('uSplit'), before: last('uBefore'), after: last('uAfter') });
        }

        const colours = Object.fromEntries(Object.keys(DENTURE_TOKENS).map((name) => [name, calls.find(([uniform]) => uniform === name)?.[1] ?? null]));

        return {
            started,
            on: canvas.classList.contains('on'),
            flatOff: flat.classList.contains('off'),
            size: [canvas.width, canvas.height],
            softwareRefused: contextOptions?.failIfMajorPerformanceCaveat === true,
            tokens: DENTURE_TOKENS,
            declared: Object.keys(declared),
            colours,
            drawn,
        };
    },

    /* Where and how big the fan of photos of the welcome opens (T034). */
    async fan({ screens }) {
        const { fanLayout } = await import(new URL('home.js', site));

        return screens.map((screen) => fanLayout(screen));
    },

    /* The WhatsApp composer of the contact page driven by a list of actions (T023). */
    async composer({ number = '5215598765432', motives = [], value = '', actions = [] }) {
        const { initComposer } = await import(new URL('contact.js', site));
        const opened = [];
        const requests = [];
        let created = 0;
        let focused = 'none';
        const node = (name, extra = {}) => ({
            name,
            listeners: {},
            attributes: {},
            classList: classList(),
            hidden: false,
            getAttribute(key) { return this.attributes[key] ?? null; },
            setAttribute(key, val) { this.attributes[key] = String(val); },
            removeAttribute(key) { delete this.attributes[key]; },
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            focus() { focused = name; },
            fire(type) { const event = { prevented: false, preventDefault() { this.prevented = true; } }; (this.listeners[type] ?? []).forEach((fn) => fn(event)); return event; },
            ...extra,
        });
        const chips = motives.map((message, index) => {
            const chip = node(`chip${index}`);
            chip.attributes['data-motivo'] = message;
            chip.attributes['aria-pressed'] = index === 0 ? 'true' : 'false';

            return chip;
        });
        const textarea = node('textarea', { value, selection: null, setSelectionRange(from, to) { this.selection = [from, to]; } });
        let html = '';
        Object.defineProperty(textarea, 'innerHTML', { get: () => html, set: (text) => { html = text; created += 1; } });
        const error = node('error');
        error.hidden = true;
        const form = node('form', {
            querySelector: (selector) => ({ textarea, '[data-wa-error]': error })[selector] ?? null,
            querySelectorAll: (selector) => (selector === '[data-motivo]' ? chips : []),
        });
        form.attributes['data-wa-number'] = number;
        const win = {
            open: (url, target, features) => { opened.push({ url, target, features }); return null; },
            fetch: (url) => { requests.push(url); },
            document: { createElement: () => { created += 1; return node('created'); } },
        };

        initComposer(form, win);

        const seen = [];
        for (const [action, argument] of actions) {
            let prevented = null;

            if (action === 'chip') {
                chips[argument].fire('click');
            } else if (action === 'type') {
                textarea.value = argument;
                textarea.fire('input');
            } else if (action === 'submit') {
                prevented = form.fire('submit').prevented;
            }

            seen.push({
                after: action,
                value: textarea.value,
                pressed: chips.map((chip) => chip.attributes['aria-pressed']),
                on: chips.map((chip) => chip.classList.contains('on')),
                invalid: textarea.attributes['aria-invalid'] ?? null,
                errorShown: !error.hidden,
                focused,
                selection: textarea.selection,
                prevented,
                opened: [...opened],
                requests: requests.length,
                created,
            });
        }

        return seen;
    },

    /* The phone menu driven by a list of actions (T041). */
    async phoneMenu({ reduced = false, actions = [], at = '2026-11-16T12:00:00-06:00', schedule = { 1: ['09:00', '19:00'] } }) {
        const { initPhoneMenu, clinicStatus, parseSchedule } = await import(new URL('core.js', site));
        const timers = [];
        let focused = 'none';
        const node = (name, extra = {}) => ({
            name,
            listeners: {},
            attributes: {},
            classList: classList(),
            hasAttribute(key) { return key in this.attributes; },
            getAttribute(key) { return this.attributes[key] ?? null; },
            setAttribute(key, value) { this.attributes[key] = String(value); },
            removeAttribute(key) { delete this.attributes[key]; },
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            focus() { focused = name; doc.activeElement = this; },
            fire(type, event = {}) { (this.listeners[type] ?? []).forEach((fn) => fn({ preventDefault() { event.prevented = true; }, ...event })); return event; },
            ...extra,
        });
        const summary = node('button');
        const links = ['Inicio', 'Galería'].map((name) => node(name));
        const tiles = ['Limpieza', 'Llamar'].map((name) => node(name));
        const statusText = { textContent: 'horario' };
        const status = node('status');
        const doc = {
            activeElement: null,
            documentElement: { style: {} },
            listeners: {},
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            removeEventListener(type, fn) { this.listeners[type] = (this.listeners[type] ?? []).filter((other) => other !== fn); },
            key(key, shiftKey = false) { const event = { key, shiftKey, prevented: false, preventDefault() { this.prevented = true; } }; (this.listeners.keydown ?? []).forEach((fn) => fn(event)); return event; },
        };
        const menu = node('menu', {
            querySelector: (selector) => ({ summary, '[data-menu-status]': status, '[data-menu-status-text]': statusText })[selector] ?? null,
            querySelectorAll: (selector) => (selector === '[data-menu-close]' ? links : selector.includes('a[href]') ? [...links, ...tiles] : []),
        });
        const win = {
            innerWidth: 390,
            listeners: {},
            matchMedia: (query) => ({ matches: Boolean(reduced) && query.includes('reduce') }),
            setTimeout: (fn, ms) => { timers.push([fn, ms]); return timers.length; },
            clearTimeout: (id) => { if (timers[id - 1]) timers[id - 1][0] = () => {}; },
            requestAnimationFrame: (fn) => fn(),
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
        };
        const sched = parseSchedule(schedule);
        const D = { clinic: () => clinicStatus(sched, new Date(at)) };
        const snapshot = (after) => ({
            after,
            open: menu.hasAttribute('open'),
            shown: menu.classList.contains('is-open'),
            expanded: summary.attributes['aria-expanded'] ?? null,
            label: summary.attributes['aria-label'] ?? null,
            locked: doc.documentElement.style.overflow === 'hidden',
            focused,
            status: statusText.textContent,
            statusOpen: status.classList.contains('open'),
            statusClosed: status.classList.contains('closed'),
        });
        const flush = () => { while (timers.length) { const [fn] = timers.shift(); fn(); } };

        initPhoneMenu(menu, win, doc, D);
        const seen = [snapshot('start')];

        for (const [action, argument] of actions) {
            if (action === 'click') {
                const event = summary.fire('click', {});
                seen.push({ ...snapshot('click'), prevented: Boolean(event.prevented) });
                continue;
            }

            if (action === 'flush') {
                flush();
            } else if (action === 'focus') {
                [...links, ...tiles, summary].find((item) => item.name === argument).focus();
            } else if (action === 'tab' || action === 'shiftTab') {
                const event = doc.key('Tab', action === 'shiftTab');
                seen.push({ ...snapshot(action), prevented: event.prevented });
                continue;
            } else if (action === 'escape') {
                doc.key('Escape');
            } else if (action === 'link') {
                links[0].fire('click');
            } else if (action === 'resize') {
                win.innerWidth = argument;
                (win.listeners.resize ?? []).forEach((fn) => fn());
            }

            seen.push(snapshot(`${action}${argument ? ' ' + argument : ''}`));
        }

        return seen;
    },

    /* The days left of the coupon of the phone menu (T042). */
    async menuCoupon({ end, at, present = true }) {
        const { fillMenuCoupon } = await import(new URL('home.js', site));
        const left = { textContent: 'Promoción vigente' };
        const coupon = { getAttribute: (key) => (key === 'data-end' ? end : null), querySelector: () => left };
        const doc = { querySelector: (selector) => (present && selector === '[data-menu-promo]' ? coupon : null) };

        fillMenuCoupon(doc, new Date(at));

        return { left: left.textContent };
    },

    /* The coupon and tickets as the server orders them: none removed nor reordered. */
    async promos({ tickets, at, card = null }) {
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
                if (selector === '[data-pm-card]') {
                    return card ? { getAttribute: (name) => card[name.replace('data-', '')] ?? null, classList: classList() } : null;
                }

                const match = selector.match(/data-pm="(\w+)"/);

                return match ? field(match[1]) : null;
            },
        };

        initPromos(root);
        globalThis.Date = RealDate;

        return {
            title: fields.title?.textContent ?? null,
            left: fields.left?.textContent ?? null,
            bar: fields.bar?.style.width ?? null,
            of: fields.of?.textContent ?? null,
            tickets: buttons.map((button) => ({ title: button.attributes.title, hidden: button.hidden, left: button.left.textContent })),
        };
    },

    /* The services ribbon of the home: its pause button, the focus and a touch (T044, R1). */
    async marquee({ actions = [] }) {
        const { initMarquee } = await import(new URL('home.js', site));
        const node = (name) => ({
            name,
            listeners: {},
            attributes: {},
            classList: classList(),
            getAttribute(key) { return this.attributes[key] ?? null; },
            setAttribute(key, val) { this.attributes[key] = String(val); },
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            fire(type, event = {}) { (this.listeners[type] ?? []).forEach((fn) => fn({ target: this, ...event })); },
        });
        const button = node('button');
        button.attributes['aria-pressed'] = 'false';
        button.attributes['aria-label'] = 'Pausar la cinta de servicios';
        const root = node('root');
        root.querySelector = (selector) => (selector === '[data-mq-pause]' ? button : null);

        initMarquee(root);

        return actions.map((action) => {
            if (action === 'click') {
                button.fire('click');
            } else if (action === 'touch') {
                root.fire('pointerdown', { pointerType: 'touch', target: root });
            } else if (action === 'mouse') {
                root.fire('pointerdown', { pointerType: 'mouse', target: root });
            } else {
                root.fire(action);
            }

            return { after: action, held: root.classList.contains('hold'), pressed: button.attributes['aria-pressed'], label: button.attributes['aria-label'] };
        });
    },

    /* How the welcome text fades out and its end block fades in along the scene, and which of them
       may take the focus (T046, R3). */
    async welcomeFade({ progress = [] }) {
        const { welcomeFade } = await import(new URL('home.js', site));

        return progress.map((value) => welcomeFade(value));
    },

    /* The testimonials: which changes are announced to a screen reader (T047, R4). */
    async testimonialsLive({ count = 3, actions = [] }) {
        const { initTestimonials } = await import(new URL('home.js', site));
        const node = (name, extra = {}) => ({
            name,
            listeners: {},
            attributes: {},
            hidden: false,
            style: {},
            textContent: '',
            offsetWidth: 0,
            classList: classList(),
            getAttribute(key) { return this.attributes[key] ?? null; },
            setAttribute(key, val) { this.attributes[key] = String(val); },
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            fire(type) { (this.listeners[type] ?? []).forEach((fn) => fn({ target: this })); },
            querySelector: () => null,
            ...extra,
        });
        const slides = Array.from({ length: count }, (_, index) => node(`slide${index}`, { hidden: index !== 0 }));
        const bars = Array.from({ length: count }, (_, index) => node(`bar${index}`));
        const dots = bars.map((bar, index) => node(`dot${index}`, { querySelector: (selector) => (selector === 'i' ? bar : null) }));
        const prev = node('prev');
        const next = node('next');
        const pause = node('pause');
        const live = node('live');
        const parts = { '[data-ts-prev]': prev, '[data-ts-next]': next, '[data-ts-pause]': pause, '[data-ts-live]': live, '[data-ts-pos]': node('pos') };
        const root = node('root', {
            querySelector: (selector) => parts[selector] ?? null,
            querySelectorAll: (selector) => ({ '[data-ts-slide]': slides, '[data-ts-dot]': dots })[selector] ?? [],
            closest: () => null,
        });
        const D = { reducedMotion: () => false };

        initTestimonials(root, {}, D);

        const current = () => slides.findIndex((slide) => !slide.hidden);

        return actions.map((action) => {
            if (action === 'auto') {
                bars[current()].fire('animationend');
            } else if (action === 'next') {
                next.fire('click');
            } else if (action === 'prev') {
                prev.fire('click');
            } else if (action.startsWith('dot')) {
                dots[Number(action.slice(3))].fire('click');
            }

            return { after: action, current: current(), live: live.attributes['aria-live'] ?? null };
        });
    },

    /* Copying the phone and the address of the contact page: the button and the status region (T049, R6). */
    async copy({ press = [] }) {
        const { initCopy } = await import(new URL('pages/contacto.js', site));
        const timers = [];
        const node = (name, attributes = {}) => ({
            name,
            listeners: {},
            attributes: { ...attributes },
            textContent: '',
            classList: classList(),
            getAttribute(key) { return this.attributes[key] ?? null; },
            toggleAttribute(key, force) { if (force) { this.attributes[key] = ''; } else { delete this.attributes[key]; } },
            addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
            querySelector: () => null,
        });
        const phone = node('phone', { 'data-copy': '+52 55 1234 5678', 'data-announce': 'Teléfono copiado' });
        const address = node('address', { 'data-copy': 'Lázaro Cárdenas, Michoacán', 'data-copied': 'Dirección copiada' });
        const label = node('label');
        label.textContent = 'Copiar dirección';
        address.querySelector = (selector) => (selector === '[data-copy-label]' ? label : null);
        const region = node('region');
        const doc = {
            querySelectorAll: (selector) => (selector === '[data-copy]' ? [phone, address] : []),
            querySelector: (selector) => (selector === '[data-ui-status]' ? region : null),
        };
        const copied = [];
        const win = {
            navigator: { clipboard: { writeText: (text) => { copied.push(text); return Promise.resolve(); } } },
            setTimeout: (fn, ms) => { timers.push({ fn, ms }); return timers.length; },
            clearTimeout: () => {},
        };

        initCopy(doc, win);

        const seen = [];
        for (const name of press) {
            const button = name === 'phone' ? phone : address;
            button.listeners.click.forEach((fn) => fn());
            await Promise.resolve();
            await Promise.resolve();
            timers.filter((timer) => timer.ms < 1000).splice(0).forEach((timer) => timer.fn());
            seen.push({ pressed: name, region: region.textContent, shown: 'data-shown' in region.attributes, label: label.textContent });
            timers.splice(0, timers.length).filter((timer) => timer.ms >= 1000).forEach((timer) => timer.fn());
        }

        return { copied, seen, regionAfter: region.textContent, shownAfter: 'data-shown' in region.attributes };
    },
};

if (!scenarios[scenario]) {
    console.error(`Unknown scenario: ${scenario}`);
    process.exit(2);
}

process.stdout.write(JSON.stringify(await scenarios[scenario](args)));
