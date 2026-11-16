/*
 * Public site (spec 026): shared utilities of every page, from core.js of the proposal in
 * nueva-landing/. It marks that JavaScript runs, stops the scripted motion when the visitor asked
 * for less motion, and works out whether the clinic is open with the opening hours the server
 * paints in data-clinic-schedule and the clock of Mexico City, whatever the visitor's zone
 * (CA7, CA9). The other scripts of the site reach it through window.Dentissa.
 */

const CLINIC_TIME_ZONE = 'America/Mexico_City';
const DAY_NAMES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const WEEKDAYS = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };

/**
 * Opening hours as the server gives them (ISO day 1–7 => ["09:00", "19:00"]) turned into minutes
 * after midnight by JavaScript day (0 = Sunday).
 */
export function parseSchedule(raw) {
    const schedule = { 0: null, 1: null, 2: null, 3: null, 4: null, 5: null, 6: null };
    const toMinutes = (time) => {
        const [hours, minutes] = String(time).split(':').map(Number);

        return hours * 60 + minutes;
    };

    Object.entries(raw || {}).forEach(([isoDay, hours]) => {
        if (Array.isArray(hours) && hours.length === 2) {
            schedule[Number(isoDay) % 7] = [toMinutes(hours[0]), toMinutes(hours[1])];
        }
    });

    return schedule;
}

export function formatTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return `${hours % 12 || 12}:${rest < 10 ? '0' : ''}${rest} ${hours < 12 ? 'a. m.' : 'p. m.'}`;
}

/** Day of the week and minutes after midnight of an instant, on the clinic's clock. */
export function clinicClock(date) {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', { timeZone: CLINIC_TIME_ZONE, weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
            .formatToParts(date)
            .map((part) => [part.type, part.value]),
    );

    return { day: WEEKDAYS[parts.weekday], minutes: Number(parts.hour) * 60 + Number(parts.minute) };
}

/** Whether the clinic is open at an instant, and the texts the pages show about it. */
export function clinicStatus(schedule, date = new Date()) {
    const { day, minutes } = clinicClock(date);
    const today = schedule[day];
    const open = Boolean(today) && minutes >= today[0] && minutes < today[1];
    let sub;
    let near;

    if (open) {
        const left = today[1] - minutes;
        near = left <= 90 ? `Cierra en ${left >= 60 ? `1 h ${left - 60} min` : `${left} min`}` : `Cierra a las ${formatTime(today[1])}`;
        sub = `Cerramos a las ${formatTime(today[1])}`;
    } else if (today && minutes < today[0]) {
        sub = `Abrimos hoy a las ${formatTime(today[0])}`;
        near = sub;
    } else {
        let next = (day + 1) % 7;

        for (let step = 0; step < 7 && !schedule[next]; step += 1) {
            next = (next + 1) % 7;
        }

        sub = schedule[next]
            ? `${next === (day + 1) % 7 ? 'Abrimos mañana' : `Abrimos el ${DAY_NAMES[next]}`} a las ${formatTime(schedule[next][0])}`
            : 'Cerrado';
        near = sub;
    }

    return {
        day,
        minutes,
        open,
        sub,
        near,
        todayName: DAY_NAMES[day],
        todayHours: today ? `${formatTime(today[0])} – ${formatTime(today[1])}` : 'Cerrado',
    };
}

function readSchedule(doc) {
    const holder = doc.querySelector('[data-clinic-schedule]');

    try {
        return parseSchedule(holder ? JSON.parse(holder.getAttribute('data-clinic-schedule')) : {});
    } catch {
        return parseSchedule({});
    }
}

function initTilt(doc, D) {
    doc.querySelectorAll('[data-tilt]').forEach((el) => {
        el.addEventListener('pointermove', (event) => {
            if (D.reducedMotion()) {
                return;
            }

            const box = el.getBoundingClientRect();
            const x = (event.clientX - box.left) / box.width - 0.5;
            const y = (event.clientY - box.top) / box.height - 0.5;
            el.style.setProperty('--ry', `${(x * 9).toFixed(2)}deg`);
            el.style.setProperty('--rx', `${(-y * 9).toFixed(2)}deg`);
        });
        el.addEventListener('pointerleave', () => {
            el.style.setProperty('--ry', '0deg');
            el.style.setProperty('--rx', '0deg');
        });
    });
}

function initParallax(doc) {
    doc.querySelectorAll('[data-parallax]').forEach((el) => {
        const move = (x, y) => {
            el.style.setProperty('--mx', x.toFixed(3));
            el.style.setProperty('--my', y.toFixed(3));
            el.dispatchEvent(new CustomEvent('dentissa:pointer', { detail: { x, y } }));
        };

        el.addEventListener('pointermove', (event) => {
            const box = el.getBoundingClientRect();
            move(((event.clientX - box.left) / box.width) * 2 - 1, ((event.clientY - box.top) / box.height) * 2 - 1);
        });
        el.addEventListener('pointerleave', () => move(0, 0));
    });
}

/** Escape closes the open folding menu and gives the focus back to its button (CA10). */
/**
 * M-1: each section enters once per visit. A block gets `in` the first time it reaches the screen and
 * is no longer watched; without IntersectionObserver every block gets it at once. Without JavaScript
 * or with less motion the CSS shows them from the first frame.
 */
export function revealOnce(doc, win) {
    const blocks = [...doc.querySelectorAll('.reveal, .reveal3d')];

    if (!('IntersectionObserver' in win)) {
        blocks.forEach((block) => block.classList.add('in'));

        return;
    }

    const observer = new win.IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    blocks.forEach((block) => observer.observe(block));
}

export function closeMenuOnEscape(doc, event) {
    if (event.key !== 'Escape') {
        return false;
    }

    const menu = doc.querySelector('details.menu-m[open]');

    /* A menu driven by initPhoneMenu closes itself, with its animation. */
    if (!menu || menu.dentissaMenu) {
        return false;
    }

    const summary = menu.querySelector('summary');

    menu.removeAttribute('open');
    summary?.setAttribute('aria-expanded', 'false');
    summary?.focus();

    return true;
}

/** The button of a folding menu says whether it is open, as a native <details> does not (CA10). */
function initMenus(doc) {
    doc.querySelectorAll('details.menu-m').forEach((menu) => {
        const summary = menu.querySelector('summary');
        const sync = () => summary?.setAttribute('aria-expanded', menu.hasAttribute('open') ? 'true' : 'false');

        sync();
        menu.addEventListener('toggle', sync);
    });
}

const MENU_CLOSE_MS = 520;
const MENU_WIDE = 920;

/** What the dock of the phone menu says about the clinic: open or closed, and until when (CA7). */
export function menuStatus(status) {
    const near = status.near.charAt(0).toLowerCase() + status.near.slice(1);

    return { open: status.open, text: `${status.open ? 'Abierto ahora' : 'Cerrado'} · ${near}` };
}

/**
 * The phone menu (T041, redesigned by the user): a <details> whose sheet grows in a circle from its
 * button (CSS, class is-open). Opening locks the page, names the button "Cerrar menú", puts the
 * focus on the first link and keeps it inside; Escape, a page link or a screen wider than 920 px
 * close it, and the close waits for the animation before dropping `open` (at once with less
 * motion). The dock says whether the clinic is open on the clinic's clock.
 */
export function initPhoneMenu(menu, win = window, doc = document, D = win.Dentissa) {
    const summary = menu.querySelector('summary');

    if (!summary) {
        return null;
    }

    const reduced = () => win.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const focusables = () => [...menu.querySelectorAll('.mn-sheet a[href], .mn-sheet button:not([disabled])'), summary];
    let closing = null;

    const label = (open) => {
        summary.setAttribute('aria-expanded', open ? 'true' : 'false');
        summary.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    };
    const paintStatus = () => {
        const holder = menu.querySelector('[data-menu-status]');
        const text = menu.querySelector('[data-menu-status-text]');

        if (!holder || !text || !D?.clinic) {
            return;
        }

        const { open, text: words } = menuStatus(D.clinic());
        holder.classList.toggle('open', open);
        holder.classList.toggle('closed', !open);
        text.textContent = words;
    };
    const onKey = (event) => {
        if (event.key === 'Escape') {
            close();
            summary.focus();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const items = focusables();
        const index = items.indexOf(doc.activeElement);

        if (event.shiftKey && index <= 0) {
            event.preventDefault();
            items[items.length - 1].focus();
        } else if (!event.shiftKey && index === items.length - 1) {
            event.preventDefault();
            items[0].focus();
        }
    };
    const open = () => {
        if (closing !== null) {
            win.clearTimeout(closing);
            closing = null;
        }

        menu.setAttribute('open', '');
        win.requestAnimationFrame(() => menu.classList.add('is-open'));
        label(true);
        doc.documentElement.style.overflow = 'hidden';
        paintStatus();
        doc.addEventListener('keydown', onKey);
        win.setTimeout(() => focusables()[0]?.focus({ preventScroll: true }), 60);
    };
    const close = () => {
        if (!menu.hasAttribute('open')) {
            return;
        }

        menu.classList.remove('is-open');
        label(false);
        doc.documentElement.style.overflow = '';
        doc.removeEventListener('keydown', onKey);

        if (reduced()) {
            menu.removeAttribute('open');

            return;
        }

        closing = win.setTimeout(() => {
            closing = null;
            menu.removeAttribute('open');
        }, MENU_CLOSE_MS);
    };

    summary.addEventListener('click', (event) => {
        event.preventDefault();

        if (menu.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    });
    menu.querySelectorAll('[data-menu-close]').forEach((link) => link.addEventListener('click', close));
    win.addEventListener('resize', () => {
        if (win.innerWidth > MENU_WIDE) {
            close();
        }
    });
    label(false);
    paintStatus();
    menu.dentissaMenu = { open, close };

    return menu.dentissaMenu;
}

/** Wires the page: called on import in the browser, and by the tests with a fake document. */
export function initCore(win = window, doc = document) {
    const D = (win.Dentissa = win.Dentissa || {});
    const schedule = readSchedule(doc);

    D.reducedMotion = () => Boolean(win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches);
    D.setIf = (root, name, on) => {
        (root || doc).querySelectorAll(`[data-if="${name}"]`).forEach((el) => {
            el.hidden = !on;
        });
    };
    D.ref = (name, root) => (root || doc).querySelector(`[data-ref="${name}"]`);
    D.schedule = schedule;
    D.fmtTime = formatTime;
    D.clinic = (date) => clinicStatus(schedule, date);
    D.ready = (fn) => {
        if (doc.readyState !== 'loading') {
            fn();
        } else {
            doc.addEventListener('DOMContentLoaded', fn);
        }
    };

    doc.documentElement.classList.add('js');

    if (D.reducedMotion()) {
        doc.documentElement.classList.add('still');
    }

    doc.addEventListener('keydown', (event) => closeMenuOnEscape(doc, event));

    D.ready(() => {
        initMenus(doc);
        doc.querySelectorAll('details.menu-m').forEach((menu) => initPhoneMenu(menu, win, doc, D));
        initTilt(doc, D);
        initParallax(doc);
        revealOnce(doc, win);
    });

    return D;
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    initCore();
}
