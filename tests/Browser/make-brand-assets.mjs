#!/usr/bin/env node
/*
 * Brand assets (spec 016, CA15): draws, from the originals of the clinic's logo, the files the
 * interface serves. Node and headless Chrome only - Chrome scales each image and Node packs
 * the icon into favicon.ico - so nothing is added to package.json.
 *
 *   npm run brand:assets
 *
 * The originals are not versioned (storage/app/public/Logos_Melissa_Lopez/ and
 * storage/app/public/login.jpg); what this writes is: public/images/brand/ and
 * public/favicon.ico. Running it again gives the same files.
 */
import { spawnSync } from 'node:child_process';
import { copyFileSync, existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const originals = join(root, 'storage/app/public');
const brand = join(root, 'public/images/brand');

const ICON = join(originals, 'Logos_Melissa_Lopez/con_fondo/icono_blanco_fondo_rosa.png');
const LOGO = join(originals, 'Logos_Melissa_Lopez/sin_fondo/logo_horizontal_color_transparente.png');
const ACCESS = join(originals, 'login.jpg');

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

/** Width and height of a PNG, from its header. */
function sizeOf(file) {
    const header = readFileSync(file).subarray(16, 24);

    return { width: header.readUInt32BE(0), height: header.readUInt32BE(4) };
}

/** Draws a picture at the given size on a transparent page and saves what Chrome paints. */
function scale(chrome, work, source, width, height, target) {
    const page = join(work, 'page.html');

    writeFileSync(page, `<!doctype html><meta charset="utf-8"><style>html,body{margin:0;background:transparent}img{display:block;width:${width}px;height:${height}px}</style><img src="${pathToFileURL(source).href}">`);

    const result = spawnSync(chrome, [
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        '--force-device-scale-factor=1',
        '--force-color-profile=srgb',
        '--default-background-color=00000000',
        `--user-data-dir=${join(work, 'profile')}`,
        `--window-size=${width},${height}`,
        `--screenshot=${target}`,
        ...(process.env.CI ? ['--no-sandbox'] : []),
        pathToFileURL(page).href,
    ], { stdio: 'ignore' });

    if (result.status !== 0 || !existsSync(target)) {
        throw new Error(`Chrome no pudo dibujar ${target}`);
    }
}

/** An .ico that holds each PNG as it is, which every current browser reads. */
function ico(pngs) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(1, 2);
    header.writeUInt16LE(pngs.length, 4);

    let offset = 6 + 16 * pngs.length;
    const entries = pngs.map(({ size, data }) => {
        const entry = Buffer.alloc(16);
        entry.writeUInt8(size, 0);
        entry.writeUInt8(size, 1);
        entry.writeUInt16LE(1, 4);
        entry.writeUInt16LE(32, 6);
        entry.writeUInt32LE(data.length, 8);
        entry.writeUInt32LE(offset, 12);
        offset += data.length;

        return entry;
    });

    return Buffer.concat([header, ...entries, ...pngs.map(({ data }) => data)]);
}

for (const original of [ICON, LOGO, ACCESS]) {
    if (!existsSync(original)) {
        console.error(`brand:assets: falta el original ${original}`);
        process.exit(2);
    }
}

const chrome = findChrome();
const work = mkdtempSync(join(tmpdir(), 'brand-assets-'));

try {
    mkdirSync(brand, { recursive: true });

    for (const size of [64, 180, 192, 512]) {
        scale(chrome, work, ICON, size, size, join(brand, `icon-${size}.png`));
    }

    const logo = sizeOf(LOGO);
    scale(chrome, work, LOGO, 520, Math.round((520 * logo.height) / logo.width), join(brand, 'logo.png'));

    copyFileSync(ACCESS, join(brand, 'access.jpg'));

    const favicon = [16, 32, 48].map((size) => {
        const file = join(work, `favicon-${size}.png`);
        scale(chrome, work, ICON, size, size, file);

        return { size, data: readFileSync(file) };
    });
    writeFileSync(join(root, 'public/favicon.ico'), ico(favicon));
} finally {
    rmSync(work, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
}

console.log('Activos de marca escritos en public/images/brand/ y public/favicon.ico.');
