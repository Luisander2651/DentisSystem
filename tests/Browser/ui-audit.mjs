#!/usr/bin/env node
/*
 * ui-audit (spec 016): opens every screen and dialog of tests/Browser/screens.json in a
 * headless Chrome, at 390 × 844 and 1440 × 900, and runs the check modules on each one.
 * Node and Chrome only, over the DevTools protocol: no dependencies.
 *
 *   npm run test:ui                                   every entry, against the local Docker app
 *   npm run test:ui -- --only pacientes               one screen (without its dialogs)
 *   npm run test:ui -- --only crear-paciente          one dialog, on every screen that mounts it
 *   npm run test:ui -- --checks global,a11y           only some check modules
 *   npm run test:ui -- --width 390                    only one width
 *   npm run test:ui -- --baseline                     rewrite tests/Browser/baseline-1440.json
 *   npm run test:ui -- --screenshots [dir]            save a capture of every entry
 *   npm run test:ui -- --jobs 1                       tabs open at once (4 by default)
 *   npm run test:ui -- --base-url http://127.0.0.1:8000 --artisan "php artisan"     (CI)
 *
 * It exits with 1 when a check reports a failure or an entry cannot be opened, and with 2
 * when it cannot run at all. The data comes from `php artisan ui:audit-data` and the
 * sessions from `php artisan ui:audit-session {rol}`: no password lives in the repository.
 */
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const CHECK_MODULES = ['composition', 'global', 'dialogs', 'screens', 'a11y'];
const ROLES = ['administrador', 'asistente', 'doctor', 'paciente'];
const VIEWPORTS = { 390: 844, 1440: 900 };
const SETTLE_TIMEOUT = 15000;

class Cdp {
    #socket;

    #nextId = 0;

    #pending = new Map();

    #listeners = new Set();

    static connect(url) {
        return new Promise((resolveConnection, reject) => {
            const cdp = new Cdp();
            cdp.#socket = new WebSocket(url);
            cdp.#socket.addEventListener('open', () => resolveConnection(cdp));
            cdp.#socket.addEventListener('error', () => reject(new Error(`No pude conectar con Chrome en ${url}`)));
            cdp.#socket.addEventListener('message', (event) => cdp.#receive(JSON.parse(event.data)));
        });
    }

    #receive(message) {
        if (message.id !== undefined) {
            const waiting = this.#pending.get(message.id);
            this.#pending.delete(message.id);
            if (message.error) {
                waiting?.reject(new Error(`${waiting.method}: ${message.error.message}`));
            } else {
                waiting?.resolve(message.result);
            }

            return;
        }

        for (const listener of this.#listeners) {
            listener(message);
        }
    }

    send(method, params = {}, sessionId = undefined) {
        const id = ++this.#nextId;

        return new Promise((resolveCall, reject) => {
            this.#pending.set(id, { resolve: resolveCall, reject, method });
            this.#socket.send(JSON.stringify({ id, method, params, sessionId }));
        });
    }

    listen(listener) {
        this.#listeners.add(listener);

        return () => this.#listeners.delete(listener);
    }

    close() {
        this.#socket.close();
    }
}

/** One open tab at one width, with what a check module needs to look at it. */
class Page {
    #cdp;

    #sessionId;

    #inflight = new Set();

    #lastActivity = Date.now();

    consoleErrors = [];

    /** Seconds the API asked to wait (429), or null when it never limited this page. */
    rateLimited = null;

    constructor(cdp, sessionId, width, height, origin) {
        this.#cdp = cdp;
        this.#sessionId = sessionId;
        this.width = width;
        this.height = height;

        cdp.listen((message) => {
            if (message.sessionId !== sessionId) {
                return;
            }

            const { method, params } = message;

            if (method === 'Network.requestWillBeSent') {
                // Third-party embeds (the map of the contact page) never go idle.
                if (params.request.url.startsWith(origin)) {
                    this.#inflight.add(params.requestId);
                    this.#lastActivity = Date.now();
                }
            } else if (method === 'Network.responseReceived') {
                if (params.response.status === 429 && params.response.url.startsWith(origin)) {
                    const headers = Object.fromEntries(Object.entries(params.response.headers).map(([name, value]) => [name.toLowerCase(), value]));
                    this.rateLimited = Number(headers['retry-after']) || 60;
                }
            } else if (method === 'Network.loadingFinished' || method === 'Network.loadingFailed') {
                this.#inflight.delete(params.requestId);
                this.#lastActivity = Date.now();
            } else if (method === 'Page.javascriptDialogOpening') {
                this.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
            } else if (method === 'Runtime.exceptionThrown') {
                this.consoleErrors.push(params.exceptionDetails.exception?.description ?? params.exceptionDetails.text);
            }
        });
    }

    send(method, params = {}) {
        return this.#cdp.send(method, params, this.#sessionId);
    }

    /** Runs a function (or an expression) inside the page and returns its JSON value. */
    async evaluate(code, ...args) {
        const expression = typeof code === 'function'
            ? `(${code})(${args.map((arg) => JSON.stringify(arg)).join(', ')})`
            : code;
        const { result, exceptionDetails } = await this.send('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true,
            userGesture: true,
        });

        if (exceptionDetails) {
            throw new Error(exceptionDetails.exception?.description ?? exceptionDetails.text);
        }

        return result.value;
    }

    /** Waits for a condition, never for a fixed time. */
    async waitFor(code, { timeout = 8000, args = [], what = 'la condición' } = {}) {
        const deadline = Date.now() + timeout;

        for (;;) {
            if (await this.evaluate(code, ...args)) {
                return;
            }

            if (Date.now() > deadline) {
                throw new Error(`Se agotó la espera de ${what}`);
            }

            await new Promise((done) => setTimeout(done, 50));
        }
    }

    /** No request in flight and none started in the last 300 ms. */
    async settle() {
        const deadline = Date.now() + SETTLE_TIMEOUT;

        while (Date.now() < deadline) {
            if (this.#inflight.size === 0 && Date.now() - this.#lastActivity > 300) {
                return;
            }

            await new Promise((done) => setTimeout(done, 50));
        }

        throw new Error('La pantalla no terminó de cargar (peticiones en curso)');
    }

    async goto(url) {
        await this.send('Page.navigate', { url });
        await this.waitFor('document.readyState === "complete"', { timeout: SETTLE_TIMEOUT, what: 'la carga de la página' });
        await this.settle();
    }

    isVisible(selector) {
        return this.evaluate(isVisibleInPage, selector);
    }

    async click(selector) {
        await this.waitFor(isVisibleInPage, { args: [selector], what: `"${selector}" visible` });
        await this.evaluate((target) => {
            const element = [...document.querySelectorAll(target)].find((candidate) => candidate.getClientRects().length > 0);
            element.scrollIntoView({ block: 'center' });
            element.click();
        }, selector);
        await this.settle();
    }

    async press(key) {
        const codes = { Escape: 27, Enter: 13, Tab: 9, ' ': 32 };
        const event = { key, code: key === ' ' ? 'Space' : key, windowsVirtualKeyCode: codes[key], nativeVirtualKeyCode: codes[key] };
        await this.send('Input.dispatchKeyEvent', { type: 'keyDown', ...event, text: key === 'Enter' ? '\r' : key === ' ' ? ' ' : undefined });
        await this.send('Input.dispatchKeyEvent', { type: 'keyUp', ...event });
    }

    async screenshot(file, fullPage) {
        const { data } = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: fullPage });
        writeFileSync(file, Buffer.from(data, 'base64'));
    }
}

function isVisibleInPage(selector) {
    return [...document.querySelectorAll(selector)].some((element) => {
        const style = getComputedStyle(element);

        return element.getClientRects().length > 0 && style.visibility !== 'hidden' && style.display !== 'none';
    });
}

function parseArguments(argv) {
    const options = {
        baseUrl: 'http://localhost:8000',
        artisan: 'docker compose exec -T app php artisan',
        only: [],
        checks: null,
        widths: Object.keys(VIEWPORTS).map(Number),
        baseline: false,
        screenshots: null,
        jobs: 4,
    };

    for (let index = 0; index < argv.length; index++) {
        const argument = argv[index];
        const value = () => {
            if (argv[index + 1] === undefined) {
                throw new Error(`Falta el valor de ${argument}`);
            }

            return argv[++index];
        };

        if (argument === '--base-url') {
            options.baseUrl = value().replace(/\/+$/, '');
        } else if (argument === '--artisan') {
            options.artisan = value();
        } else if (argument === '--only') {
            options.only = value().split(',').map((item) => item.trim()).filter(Boolean);
        } else if (argument === '--checks') {
            options.checks = value().split(',').map((item) => item.trim()).filter(Boolean);
        } else if (argument === '--width') {
            options.widths = [Number(value())];
        } else if (argument === '--jobs') {
            options.jobs = Math.max(1, Number(value()) || 1);
        } else if (argument === '--baseline') {
            options.baseline = true;
        } else if (argument === '--screenshots') {
            options.screenshots = argv[index + 1] && !argv[index + 1].startsWith('--') ? resolve(value()) : join(here, 'screenshots');
        } else {
            throw new Error(`Opción desconocida: ${argument}`);
        }
    }

    for (const width of options.widths) {
        if (!VIEWPORTS[width]) {
            throw new Error(`Ancho no admitido: ${width} (390 o 1440)`);
        }
    }

    return options;
}

function findChrome() {
    const candidates = [
        process.env.CHROME_PATH,
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
    ];
    const found = candidates.find((candidate) => candidate && existsSync(candidate));

    if (!found) {
        throw new Error('No encuentro Chrome. Indica su ruta en la variable CHROME_PATH.');
    }

    return found;
}

async function launchChrome() {
    const profile = mkdtempSync(join(tmpdir(), 'ui-audit-'));
    const chrome = spawn(findChrome(), [
        '--headless=new',
        '--remote-debugging-port=0',
        `--user-data-dir=${profile}`,
        '--hide-scrollbars',
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-gpu',
        '--force-color-profile=srgb',
        ...(process.env.CI ? ['--no-sandbox'] : []),
        'about:blank',
    ], { stdio: 'ignore' });
    const portFile = join(profile, 'DevToolsActivePort');
    const deadline = Date.now() + 20000;

    while (!existsSync(portFile) || readFileSync(portFile, 'utf8').split('\n').length < 2) {
        if (Date.now() > deadline) {
            chrome.kill();
            throw new Error('Chrome no arrancó a tiempo.');
        }

        await new Promise((done) => setTimeout(done, 50));
    }

    const [port, path] = readFileSync(portFile, 'utf8').split('\n');
    const cdp = await Cdp.connect(`ws://127.0.0.1:${port}${path}`);

    return {
        cdp,
        async close() {
            cdp.close();
            chrome.kill();
            await new Promise((done) => chrome.once('exit', done));
            rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
        },
    };
}

function sessionSource(artisan) {
    const [command, ...prefix] = artisan.split(/\s+/).filter(Boolean);
    const tokens = new Map();

    return (role) => {
        if (!ROLES.includes(role)) {
            throw new Error(`Rol desconocido en screens.json: ${role}`);
        }

        if (!tokens.has(role)) {
            const result = spawnSync(command, [...prefix, 'ui:audit-session', role], { encoding: 'utf8' });

            if (result.status !== 0) {
                throw new Error(`No pude obtener la sesión de ${role}: ${(result.stderr || result.stdout || result.error?.message || '').trim()}`);
            }

            tokens.set(role, result.stdout.trim().split(/\s+/).pop());
        }

        return tokens.get(role);
    };
}

/** --only matches an entry id, a screen (without its dialogs) or a dialog on every screen. */
function selectEntries(entries, only) {
    if (only.length === 0) {
        return entries;
    }

    const selected = entries.filter((entry) => only.some((name) => entry.id === name
        || (entry.dialog === undefined && entry.screen === name)
        || entry.dialog === name));
    const unknown = only.filter((name) => !entries.some((entry) => entry.id === name || entry.screen === name || entry.dialog === name));

    if (unknown.length > 0) {
        throw new Error(`--only no coincide con ninguna entrada: ${unknown.join(', ')}`);
    }

    return selected;
}

async function loadChecks(names) {
    const checks = [];

    for (const name of names ?? CHECK_MODULES) {
        if (!CHECK_MODULES.includes(name)) {
            throw new Error(`Módulo de comprobación desconocido: ${name} (${CHECK_MODULES.join(', ')})`);
        }

        const file = join(here, `checks-${name}.mjs`);

        if (existsSync(file)) {
            checks.push({ name, ...(await import(pathToFileURL(file).href)) });
        }
    }

    return checks;
}

async function runStep(page, step) {
    if (step.click) {
        await page.click(step.click);
    } else if (step.waitFor) {
        await page.waitFor(isVisibleInPage, { args: [step.waitFor], what: `"${step.waitFor}" visible` });
    } else if (step.fill) {
        await page.evaluate((selector, text) => {
            const field = document.querySelector(selector);
            field.value = text;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }, step.fill, step.value ?? '');
        await page.settle();
    } else if (step.press) {
        await page.press(step.press);
        await page.settle();
    } else {
        throw new Error(`Paso desconocido en screens.json: ${JSON.stringify(step)}`);
    }
}

async function openEntry(browser, entry, width, options, session, config) {
    const height = VIEWPORTS[width];
    const { browserContextId } = await browser.cdp.send('Target.createBrowserContext');
    const { targetId } = await browser.cdp.send('Target.createTarget', { url: 'about:blank', browserContextId });
    const { sessionId } = await browser.cdp.send('Target.attachToTarget', { targetId, flatten: true });
    const page = new Page(browser.cdp, sessionId, width, height, options.baseUrl);

    await page.send('Page.enable');
    await page.send('Runtime.enable');
    await page.send('Network.enable');
    await page.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 768 });
    await page.send('Emulation.setTouchEmulationEnabled', { enabled: width < 768 });
    await page.send('Emulation.setTimezoneOverride', { timezoneId: config.timezone });
    await page.send('Emulation.setLocaleOverride', { locale: config.locale });
    await page.send('Page.addScriptToEvaluateOnNewDocument', {
        source: `(() => {
            const offset = new Date(${JSON.stringify(config.now)}).getTime() - Date.now();
            const RealDate = Date;
            window.Date = class extends RealDate {
                constructor(...args) { super(...(args.length === 0 ? [RealDate.now() + offset] : args)); }
                static now() { return RealDate.now() + offset; }
            };
        })();`,
    });

    if (entry.role) {
        await page.send('Network.setCookie', { name: 'auth_token', value: session(entry.role), url: options.baseUrl, httpOnly: true });
    }

    const dispose = () => browser.cdp.send('Target.disposeBrowserContext', { browserContextId }).catch(() => {});

    return { page, dispose };
}

/*
 * The API allows 100 requests a minute per user and 10 without a session. When a visit runs
 * into that limit, every tab waits what the API asks and the visit starts over: a screen
 * measured without its data would report failures that are not there.
 */
const rateLimit = { resumeAt: 0 };

async function auditEntry(browser, entry, width, options, session, config, checks, baseline) {
    for (let attempt = 1; ; attempt++) {
        const wait = rateLimit.resumeAt - Date.now();

        if (wait > 0) {
            await new Promise((done) => setTimeout(done, wait));
        }

        const { page, dispose } = await openEntry(browser, entry, width, options, session, config);

        try {
            const failures = await auditPage(page, entry, width, options, checks, baseline);

            if (page.rateLimited === null || attempt === 6) {
                return failures;
            }

            rateLimit.resumeAt = Math.max(rateLimit.resumeAt, Date.now() + (page.rateLimited + 1) * 1000);
        } finally {
            await dispose();
        }
    }
}

async function auditPage(page, entry, width, options, checks, baseline) {
    const failures = [];

    try {
        await page.goto(options.baseUrl + entry.path);

        for (const step of entry.steps ?? []) {
            await runStep(page, step);
        }
    } catch (error) {
        return [{ check: 'ui-audit', rule: 'no abre', message: error.message }];
    }

    if (entry.dialogSelector && !(await page.isVisible(entry.dialogSelector))) {
        failures.push({ check: 'ui-audit', rule: 'diálogo que no abre', message: `${entry.dialogSelector} no está a la vista tras sus pasos` });
    }

    if (options.screenshots) {
        await page.screenshot(join(options.screenshots, `${entry.id}-${width}.png`), entry.dialog === undefined);
    }

    for (const check of checks) {
        if (options.baseline && check.capture) {
            if (width === 1440) {
                baseline.captured[entry.id] = await check.capture(page, entry);
            }

            continue;
        }

        try {
            const found = await check.run(page, entry, { baseline: baseline.stored, baseUrl: options.baseUrl });
            failures.push(...found.map((failure) => ({ check: check.name, ...failure })));
        } catch (error) {
            failures.push({ check: check.name, rule: 'error del módulo', message: error.message });
        }
    }

    return failures;
}

async function main() {
    const options = parseArguments(process.argv.slice(2));
    const config = JSON.parse(readFileSync(join(here, 'screens.json'), 'utf8'));
    const entries = selectEntries(config.entries, options.only);
    const checks = await loadChecks(options.checks);
    const baselineFile = join(here, 'baseline-1440.json');
    const baseline = {
        stored: existsSync(baselineFile) ? JSON.parse(readFileSync(baselineFile, 'utf8')) : null,
        captured: {},
    };

    if (options.screenshots) {
        mkdirSync(options.screenshots, { recursive: true });
    }

    const reachable = await fetch(options.baseUrl, { redirect: 'manual' }).then(() => true, () => false);

    if (!reachable) {
        throw new Error(`La aplicación no responde en ${options.baseUrl}`);
    }

    const session = sessionSource(options.artisan);
    const browser = await launchChrome();
    const visits = entries.flatMap((entry) => options.widths
        .filter((width) => !entry.widths || entry.widths.includes(width))
        .map((width) => ({ entry, width })));
    const results = new Array(visits.length);
    let next = 0;
    let printed = 0;
    let failed = 0;

    // Results are printed in the order of screens.json, whatever order the tabs finish in.
    const report = () => {
        for (; results[printed] !== undefined; printed++) {
            const { entry, width } = visits[printed];
            const failures = results[printed];
            failed += failures.length > 0 ? 1 : 0;
            console.log(`${failures.length === 0 ? '✓' : '✗'} ${entry.id} @ ${width}`);

            for (const failure of failures) {
                console.log(`    [${failure.check}] ${failure.rule}: ${failure.message}`);
            }
        }
    };

    const worker = async () => {
        while (next < visits.length) {
            const index = next++;
            const { entry, width } = visits[index];
            results[index] = await auditEntry(browser, entry, width, options, session, config, checks, baseline);
            report();
        }
    };

    try {
        for (const role of new Set(entries.map((entry) => entry.role).filter(Boolean))) {
            session(role);
        }

        await Promise.all(Array.from({ length: Math.min(options.jobs, visits.length) }, worker));
    } finally {
        await browser.close();
    }

    const visited = visits.length;

    if (options.baseline) {
        const stored = baseline.stored ?? {};
        const merged = { ...stored, screens: { ...(stored.screens ?? {}), ...baseline.captured } };
        writeFileSync(baselineFile, `${JSON.stringify(merged, null, 4)}\n`);
        console.log(`\nLínea base escrita en ${baselineFile} (${Object.keys(baseline.captured).length} entradas).`);
    }

    console.log(`\n${visited} visitas (${entries.length} entradas), ${failed} con fallos. Módulos: ${checks.map((check) => check.name).join(', ') || 'ninguno'}.`);

    return failed === 0 ? 0 : 1;
}

main().then(
    (code) => process.exit(code),
    (error) => {
        console.error(`ui-audit: ${error.message}`);
        process.exit(2);
    },
);
