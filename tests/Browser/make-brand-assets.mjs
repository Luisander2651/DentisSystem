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
 *
 * Spec 026 (CA19): it also draws compartir.png, the 1200×630 card every page of the public site
 * shows when it is shared (docs/design/system.md → "Imagen para compartir"), with the clinic's
 * logo, the main phrase of the home and the site's own fonts and colour tokens. The card is a
 * static image: a change of the name or of the phrase in config/site.php obliges to run this
 * again, and PublicSiteSeoTest fails until TAGLINE matches it.
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

/** The main phrase of the home (config/site.php → tagline), split where the serif word begins. */
const TAGLINE = 'La mejor tecnología para tu salud dental';
const TAGLINE_HIGHLIGHT = 'salud dental';
const SUBTITLE = 'Odontología integral en Lázaro Cárdenas, Michoacán';
const ADDRESS = 'dentissapp.com';

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

/** The colour tokens of resources/css/app.css, so the card never carries a loose value. */
function tokens() {
    const css = readFileSync(join(root, 'resources/css/app.css'), 'utf8');

    return Object.fromEntries([...css.matchAll(/--color-([a-z0-9-]+):\s*(#[0-9a-f]{6})/gi)].map((match) => [match[1], match[2]]));
}

/**
 * The share card: the hero of the home in small — the phrase in Inter Tight with its serif
 * highlight and the secondary underline, the logo above and the fanned cards of the welcome on the
 * right, with the icon on top. Everything that must be read stays inside a 64 px safe zone.
 */
function shareCard(chrome, work, target) {
    const color = tokens();
    const font = (file) => pathToFileURL(join(root, 'resources/fonts', file)).href;
    const plain = TAGLINE.slice(0, TAGLINE.length - TAGLINE_HIGHLIGHT.length).trim();
    const page = join(work, 'card.html');

    writeFileSync(page, `<!doctype html><meta charset="utf-8"><style>
@font-face{font-family:'Inter Tight';font-weight:100 900;src:url('${font('inter-tight-latin.woff2')}') format('woff2')}
@font-face{font-family:'Instrument Serif';font-style:italic;src:url('${font('instrument-serif-italic-latin.woff2')}') format('woff2')}
html,body{margin:0;width:1200px;height:630px;overflow:hidden}
body{position:relative;background:${color.canvas};font-family:'Inter Tight',sans-serif;color:${color.ink}}
.glow{position:absolute;right:-160px;top:-220px;width:760px;height:760px;border-radius:50%;background:radial-gradient(circle,${color['primary-soft']} 0%,${color['secondary-soft']} 45%,transparent 70%)}
.copy{position:absolute;left:72px;top:64px;width:640px;height:502px;display:flex;flex-direction:column}
.logo{height:80px;width:auto;align-self:flex-start}
h1{margin:auto 0 0;font-size:72px;line-height:1.02;font-weight:600;letter-spacing:-0.045em}
.hl{position:relative;display:inline-block;color:${color.primary};font-family:'Instrument Serif',serif;font-style:italic;font-weight:400;font-size:1.14em;letter-spacing:-0.005em;line-height:.9;padding-right:.04em}
.hl svg{position:absolute;left:-2%;bottom:-0.16em;width:104%;height:.32em}
.hl path{stroke:${color.secondary}}
p{margin:26px 0 0;font-size:28px;line-height:1.3;color:${color.muted};font-weight:500;letter-spacing:-0.01em}
.url{margin-top:auto;display:inline-flex;align-items:center;gap:12px;font-size:24px;font-weight:600;color:${color['primary-deep']}}
.url i{width:12px;height:12px;border-radius:50%;background:${color.primary}}
.fan{position:absolute;right:88px;top:120px;width:300px;height:390px}
.fan b{position:absolute;inset:0;border-radius:24px}
.fan b:nth-child(1){background:${color['secondary-soft']};border:2px solid ${color.secondary};transform:rotate(-12deg) translate(-40px,10px)}
.fan b:nth-child(2){background:${color['primary-tint-2']};transform:rotate(-5deg) translate(-14px,0)}
.fan b:nth-child(3){background:${color.primary};transform:rotate(4deg);display:flex;align-items:center;justify-content:center;box-shadow:0 30px 60px -20px ${color['primary-deep']}}
.fan img{width:220px;height:220px;border-radius:24px}
</style>
<div class="glow"></div>
<div class="copy">
<img class="logo" src="${pathToFileURL(LOGO).href}" alt="">
<h1>${plain} <span class="hl">${TAGLINE_HIGHLIGHT}<svg viewBox="0 0 300 20" preserveAspectRatio="none"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg></span></h1>
<p>${SUBTITLE}</p>
<span class="url"><i></i>${ADDRESS}</span>
</div>
<div class="fan"><b></b><b></b><b><img src="${pathToFileURL(ICON).href}" alt=""></b></div>`);

    const result = spawnSync(chrome, [
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        '--force-device-scale-factor=1',
        '--force-color-profile=srgb',
        '--virtual-time-budget=3000',
        `--user-data-dir=${join(work, 'profile')}`,
        '--window-size=1200,630',
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

    shareCard(chrome, work, join(brand, 'compartir.png'));
} finally {
    rmSync(work, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
}

console.log('Activos de marca escritos en public/images/brand/ (con compartir.png) y public/favicon.ico.');
