/*
 * Public site (spec 026): the tour of the clinic in the gallery, from gallery.js of the proposal.
 * With three or more photos the section stays in place when it reaches the top of the screen and
 * the photos pass sideways while the visitor scrolls down; then the page goes on. The section is
 * as tall as the sideways distance, so any number of photos takes the same scroll per photo.
 * With less motion requested the row keeps scrolling sideways by itself (no pinning).
 * The before-and-after comparator (T031) and its 3D denture (T032) follow the tour.
 */

const TURN_DEGREES = 26;
const SHRINK = 0.1;

/** Progress of the tour, 0 to 1, from where the section is and how far its pinned part can go. */
export function tourProgress({ pinTop, sectionTop, sectionHeight, pinnedHeight }) {
    const span = sectionHeight - pinnedHeight;

    if (span <= 0) {
        return 0;
    }

    return Math.min(Math.max((pinTop - sectionTop) / span, 0), 1);
}

/** Turn and size of a photo by its distance to the centre of the screen, as the proposal does. */
export function frameTilt(frameCentre, screenWidth) {
    const half = screenWidth / 2;
    const distance = Math.max(-1.4, Math.min(1.4, (frameCentre - half) / half));

    return { turn: -distance * TURN_DEGREES, scale: 1 - Math.min(Math.abs(distance), 1) * SHRINK };
}

export function initTour(section, win = window) {
    const pinned = section.querySelector('.hz-sticky');
    const view = section.querySelector('.hz-view');
    const track = section.querySelector('.hz-track');

    if (!pinned || !view || !track || win.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return false;
    }

    section.classList.add('is-scrub');
    view.removeAttribute('tabindex');
    view.scrollLeft = 0;

    const distance = () => Math.max(0, track.scrollWidth - view.clientWidth);
    const size = () => section.style.setProperty('--hz-height', `${pinned.offsetHeight + distance()}px`);

    let queued = false;
    const paint = () => {
        queued = false;
        const box = section.getBoundingClientRect();

        if (box.bottom < 0 || box.top > win.innerHeight) {
            return;
        }

        const progress = tourProgress({
            pinTop: parseFloat(win.getComputedStyle(pinned).top) || 0,
            sectionTop: box.top,
            sectionHeight: box.height,
            pinnedHeight: pinned.offsetHeight,
        });

        track.style.setProperty('--tx', `${(-progress * distance()).toFixed(1)}px`);
        section.style.setProperty('--p', progress.toFixed(4));

        for (const frame of track.children) {
            const frameBox = frame.getBoundingClientRect();
            const { turn, scale } = frameTilt(frameBox.left + frameBox.width / 2, win.innerWidth);
            frame.style.setProperty('--ry', `${turn.toFixed(2)}deg`);
            frame.style.setProperty('--sc', scale.toFixed(3));
        }
    };
    const queue = () => {
        if (!queued) {
            queued = true;
            win.requestAnimationFrame(paint);
        }
    };

    size();
    paint();
    win.addEventListener('scroll', queue, { passive: true });
    win.addEventListener('resize', () => {
        size();
        queue();
    });
    win.addEventListener('load', () => {
        size();
        queue();
    }, { once: true });

    return true;
}

/* ---------------------------------------------------------------------------------------------
 * Before and after (T031, CA18): the comparator of the proposal. The slider is a native range,
 * so the arrows, Home and End move it and it fires "input"; the line, the cut of the "after" and
 * aria-valuenow follow. The flat drawing stays when the 3D denture cannot be drawn; its colours
 * come from the tokens. The names and texts of the treatments are the page's.
 * ------------------------------------------------------------------------------------------- */

/** The tokens of the flat drawing: natural, stained and whitened enamel. */
export const DRAWING_TOKENS = { natural: '--color-enamel', stain: '--color-enamel-stain', white: '--color-enamel-white' };

const UPPER_WIDTHS = [7.6, 8.6, 9.6, 11.4, 11.4, 9.6, 8.6, 7.6];
const UPPER_HEIGHTS = [24, 28, 31, 36, 36, 31, 28, 24];
const LOWER_WIDTHS = [6.8, 7.4, 7.8, 8.4, 8.4, 7.8, 7.4, 6.8];
const LOWER_HEIGHTS = [19, 21, 22, 23, 23, 22, 21, 19];
const CROOKED_TURNS = [9, -11, 7, -5, 8, -13, 10, -7];
const CROOKED_SHIFTS = [4, -3, 6, 0, -4, 5, -2, 3];
const SWEEP_SECONDS = 3.2;

/** The teeth of the flat drawing for a treatment, before or after: lower ones first, as in a bite. */
export function drawingTeeth(mode, after, colours) {
    const teeth = [];
    const build = (widths, heights, upper) => {
        const total = widths.reduce((sum, width) => sum + width, 0) + 0.5 * (widths.length - 1);
        let x = (100 - total) / 2;

        widths.forEach((width, index) => {
            const k = Math.abs(index - 3.5);
            const height = heights[index];
            const top = upper ? 24 + k * k * 0.9 : 75 - height - k * k * 0.55;
            let colour = colours.natural;
            let turn = 0;
            let shift = 0;
            let opacity = 1;

            if (mode === 'white') {
                colour = after ? colours.white : colours.stain;
            }

            if (mode === 'braces' && !after) {
                turn = CROOKED_TURNS[index] * (upper ? 1 : -0.7);
                shift = CROOKED_SHIFTS[index] * (upper ? 1 : -0.6);
            }

            if (mode === 'implant' && !after && ((upper && index === 5) || (!upper && index === 2))) {
                opacity = 0;
            }

            teeth.push({
                left: `${x.toFixed(2)}%`,
                top: `${top.toFixed(2)}%`,
                width: `${width}%`,
                height: `${height}%`,
                background: colour,
                opacity: String(opacity),
                borderRadius: upper ? '30% 30% 46% 46% / 18% 18% 50% 50%' : '46% 46% 30% 30% / 50% 50% 18% 18%',
                transform: `rotate(${turn}deg) translateY(${shift}%)`,
            });
            x += width + 0.5;
        });
    };

    build(LOWER_WIDTHS, LOWER_HEIGHTS, false);
    build(UPPER_WIDTHS, UPPER_HEIGHTS, true);

    return teeth;
}

function paintTeeth(box, teeth, doc) {
    if (box.children.length !== teeth.length) {
        box.replaceChildren();
        teeth.forEach(() => {
            const tooth = doc.createElement('div');
            tooth.className = 'tooth';
            box.appendChild(tooth);
        });
    }

    teeth.forEach((style, index) => Object.assign(box.children[index].style, style));
}

/**
 * Wires the comparator inside `root`. Returns its state ({ mode, pos }), which the 3D denture
 * reads, or null when the page has no comparator.
 */
export function initCompare(root, win = window, doc = document) {
    const before = root.querySelector('[data-teeth="before"]');
    const after = root.querySelector('[data-teeth="after"]');
    const layer = root.querySelector('[data-ba-after]');
    const handle = root.querySelector('.ba-handle');
    const range = root.querySelector('.ba-range');
    const title = root.querySelector('[data-ba-title]');
    const text = root.querySelector('[data-ba-text]');
    const sparkles = root.querySelector('[data-if="isWhite"]');
    const buttons = [...root.querySelectorAll('[data-ba]')];

    if (!before || !after || !layer || !handle || !range || !buttons.length) {
        return null;
    }

    const styles = win.getComputedStyle(doc.documentElement);
    const colours = Object.fromEntries(Object.entries(DRAWING_TOKENS).map(([name, token]) => [name, styles.getPropertyValue(token).trim()]));
    const state = { mode: buttons[0].getAttribute('data-ba'), pos: 50 };
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let touched = false;

    const setPos = (value) => {
        state.pos = Number(value);
        layer.style.clipPath = `inset(0 0 0 ${value}%)`;
        handle.style.left = `${value}%`;
        range.value = String(value);
        range.setAttribute('aria-valuenow', String(value));
    };
    const setMode = (button) => {
        state.mode = button.getAttribute('data-ba');
        paintTeeth(before, drawingTeeth(state.mode, false, colours), doc);
        paintTeeth(after, drawingTeeth(state.mode, true, colours), doc);

        if (sparkles) {
            sparkles.hidden = state.mode !== 'white';
        }

        if (title) {
            title.textContent = button.textContent.trim();
        }

        if (text) {
            text.textContent = button.getAttribute('data-ba-summary') ?? '';
        }

        buttons.forEach((other) => {
            const on = other === button;
            other.classList.toggle('on', on);
            other.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    };

    buttons.forEach((button) => button.addEventListener('click', () => setMode(button)));
    range.addEventListener('input', () => {
        touched = true;
        setPos(range.value);
    });
    setMode(buttons[0]);
    setPos(50);

    if (!reduced && win.IntersectionObserver) {
        const observer = new win.IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) {
                return;
            }

            observer.disconnect();
            let start = null;
            const step = (now) => {
                if (touched) {
                    return;
                }

                start ??= now;
                const seconds = (now - start) / 1000;

                if (seconds > SWEEP_SECONDS) {
                    setPos(50);

                    return;
                }

                const envelope = Math.sin((Math.min(seconds / SWEEP_SECONDS, 1)) * Math.PI);
                setPos(Math.round(50 + Math.sin(seconds * 2.2) * 32 * envelope));
                win.requestAnimationFrame(step);
            };

            win.requestAnimationFrame(step);
        }, { threshold: 0.45 });

        observer.observe(root);
    }

    return state;
}
