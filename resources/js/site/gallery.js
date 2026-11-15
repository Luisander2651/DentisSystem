/*
 * Public site (spec 026): the tour of the clinic in the gallery, from gallery.js of the proposal.
 * With three or more photos the section stays in place when it reaches the top of the screen and
 * the photos pass sideways while the visitor scrolls down; then the page goes on. The section is
 * as tall as the sideways distance, so any number of photos takes the same scroll per photo.
 * With less motion requested the row keeps scrolling sideways by itself (no pinning).
 * The before-and-after comparison joins this file in T020.
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
