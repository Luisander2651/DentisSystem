/*
 * ui-audit · what every check module shares (spec 016).
 *
 * IN_PAGE_LIBRARY runs inside each page before its own scripts and leaves `window.__audit`
 * with the measurements the modules repeat: what is visible, the colour actually painted
 * behind an element and its contrast, and a short description to name an element in a report.
 */
import { inflateSync } from 'node:zlib';

export const IN_PAGE_LIBRARY = `(() => {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const parsed = new Map();

    // Any CSS colour (rgb, oklch, a name…) as [red, green, blue, alpha].
    const rgba = (color) => {
        if (!parsed.has(color)) {
            context.clearRect(0, 0, 1, 1);
            context.fillStyle = 'rgba(0, 0, 0, 0)';
            context.fillStyle = color;
            context.fillRect(0, 0, 1, 1);
            const [red, green, blue, alpha] = context.getImageData(0, 0, 1, 1).data;
            parsed.set(color, [red, green, blue, alpha / 255]);
        }

        return parsed.get(color);
    };

    const over = (top, bottom) => [0, 1, 2].map((i) => top[i] * top[3] + bottom[i] * (1 - top[3])).concat(1);

    const luminance = ([red, green, blue]) => {
        const [r, g, b] = [red, green, blue].map((value) => {
            const channel = value / 255;

            return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
        });

        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    const contrast = (a, b) => {
        const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

        return (light + 0.05) / (dark + 0.05);
    };

    const visible = (element) => {
        if (!element.isConnected) {
            return false;
        }

        const box = element.getBoundingClientRect();
        const style = getComputedStyle(element);

        return box.width > 1 && box.height > 1 && style.visibility !== 'hidden' && style.display !== 'none' && Number(style.opacity) > 0.05;
    };

    /*
     * What is painted behind an element: the tones it can sit on (one per colour of a
     * gradient among its ancestors) and whether a picture is involved, in which case only
     * the pixels can tell.
     */
    const backdrop = (element) => {
        const layers = [];
        let image = false;

        for (let node = element; node instanceof Element; node = node.parentElement) {
            const style = getComputedStyle(node);
            const color = rgba(style.backgroundColor);
            const stops = [];

            if (style.backgroundImage !== 'none') {
                image ||= /url\\(/.test(style.backgroundImage);

                for (const match of style.backgroundImage.matchAll(/(?:rgba?|hsla?|oklch|oklab|lab|lch|color)\\([^()]*\\)|#[0-9a-f]{3,8}\\b/gi)) {
                    stops.push(rgba(match[0]));
                }
            }

            layers.push({ color, stops });

            // An opaque colour or a gradient covers whatever lies further out.
            if (color[3] === 1 || stops.length > 0) {
                break;
            }
        }

        const base = layers.pop();
        const ground = over(base.color, [255, 255, 255, 1]);
        const tones = (base.stops.length > 0 ? base.stops.map((stop) => over(stop, ground)) : [ground])
            .map((tone) => layers.reduceRight((below, layer) => over(layer.color, below), tone));

        const box = element.getBoundingClientRect();
        const stack = document.elementsFromPoint(box.left + box.width / 2, box.top + box.height / 2);
        const own = stack.indexOf(element);

        // Something painted underneath that is not an ancestor (a picture, a positioned layer).
        for (const under of own < 0 ? [] : stack.slice(own + 1)) {
            const style = getComputedStyle(under);
            const paints = rgba(style.backgroundColor)[3] > 0 || style.backgroundImage !== 'none';

            if (['IMG', 'VIDEO', 'CANVAS', 'PICTURE'].includes(under.tagName) || (paints && !under.contains(element))) {
                image = true;
            }

            if (under.contains(element) && rgba(style.backgroundColor)[3] === 1) {
                break;
            }
        }

        return { tones, image };
    };

    // The lowest contrast of a colour against every tone it can sit on.
    const worstContrast = (color, tones) => Math.min(...tones.map((tone) => contrast(over(color, tone), tone)));

    const describe = (element) => {
        const text = (element.getAttribute('aria-label') || element.textContent || element.getAttribute('placeholder') || '').replace(/\\s+/g, ' ').trim().slice(0, 40);
        const id = element.id ? '#' + element.id : '';
        const hook = [...element.attributes].find((attribute) => attribute.name.startsWith('data-'));

        return '<' + element.tagName.toLowerCase() + id + (hook ? ' ' + hook.name : '') + '>' + (text ? ' "' + text + '"' : '');
    };

    const isControl = (element) => element.matches('a[href], button, input:not([type=hidden]), select, textarea, summary, [role=button], [role=link], [role=tab], [role=checkbox], [role=switch], [role=menuitem]');

    // The root a check looks at: the open dialog of a dialog entry, or the whole page.
    const scope = (selector) => (selector ? [...document.querySelectorAll(selector)].find(visible) ?? null : document.body);

    // A colour read in the middle of a transition is neither the old one nor the new one.
    document.addEventListener('DOMContentLoaded', () => {
        const still = document.createElement('style');
        still.textContent = '*, *::before, *::after { transition: none !important; animation: none !important; }';
        document.head.append(still);
    });

    window.__audit = { rgba, over, luminance, contrast, visible, backdrop, worstContrast, describe, isControl, scope, lastClicked: null };
})();`;

/**
 * Decodes an 8-bit RGB or RGBA PNG (what Chrome's screenshots are) into its pixels.
 */
export function decodePng(buffer) {
    let offset = 8;
    let width = 0;
    let height = 0;
    let channels = 4;
    const data = [];

    while (offset < buffer.length) {
        const length = buffer.readUInt32BE(offset);
        const type = buffer.toString('ascii', offset + 4, offset + 8);
        const body = buffer.subarray(offset + 8, offset + 8 + length);

        if (type === 'IHDR') {
            width = body.readUInt32BE(0);
            height = body.readUInt32BE(4);
            channels = { 2: 3, 6: 4 }[body[9]];

            if (body[8] !== 8 || !channels) {
                throw new Error('Formato de PNG no admitido');
            }
        } else if (type === 'IDAT') {
            data.push(body);
        }

        offset += 12 + length;
    }

    const raw = inflateSync(Buffer.concat(data));
    const stride = width * channels;
    const pixels = Buffer.alloc(height * stride);

    for (let y = 0; y < height; y++) {
        const filter = raw[y * (stride + 1)];
        const line = raw.subarray(y * (stride + 1) + 1, (y + 1) * (stride + 1));

        for (let x = 0; x < stride; x++) {
            const left = x >= channels ? pixels[y * stride + x - channels] : 0;
            const up = y > 0 ? pixels[(y - 1) * stride + x] : 0;
            const upLeft = x >= channels && y > 0 ? pixels[(y - 1) * stride + x - channels] : 0;
            let predicted = 0;

            if (filter === 1) {
                predicted = left;
            } else if (filter === 2) {
                predicted = up;
            } else if (filter === 3) {
                predicted = (left + up) >> 1;
            } else if (filter === 4) {
                const estimate = left + up - upLeft;
                const [toLeft, toUp, toUpLeft] = [Math.abs(estimate - left), Math.abs(estimate - up), Math.abs(estimate - upLeft)];
                predicted = toLeft <= toUp && toLeft <= toUpLeft ? left : toUp <= toUpLeft ? up : upLeft;
            }

            pixels[y * stride + x] = (line[x] + predicted) & 0xff;
        }
    }

    return { width, height, channels, pixels };
}

const luminance = ([red, green, blue]) => {
    const [r, g, b] = [red, green, blue].map((value) => {
        const channel = value / 255;

        return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

/**
 * The darkest and the lightest tone of a capture, leaving out the 2 % at each end so that a
 * stray pixel does not decide.
 */
export function toneRange(png) {
    const tones = [];

    for (let index = 0; index < png.pixels.length; index += png.channels) {
        const color = [png.pixels[index], png.pixels[index + 1], png.pixels[index + 2]];
        tones.push({ color, luminance: luminance(color) });
    }

    tones.sort((a, b) => a.luminance - b.luminance);

    return {
        darkest: tones[Math.floor(tones.length * 0.02)].color,
        lightest: tones[Math.ceil(tones.length * 0.98) - 1].color,
    };
}

export const contrastOf = (a, b) => {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
};
