/*
 * Public site (spec 026): behaviour of the home page, from home.js of the proposal in nueva-landing/.
 * The photo deck of the welcome, the questions, the promotions, the certificate viewer, the
 * testimonials and the open/closed strip. Promotions arrive current and ordered from the server
 * (CA2, A16): this script only shows them. Texts are set with textContent (TM3). It reaches the
 * shared utilities of core.js through window.Dentissa.
 */

const DAY = 86400000;
const CLINIC_TIME_ZONE = 'America/Mexico_City';
const clamp = (value) => Math.min(Math.max(value, 0), 1);

/** The clinic's date (YYYY-MM-DD) at an instant. */
export function clinicDate(date) {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', { timeZone: CLINIC_TIME_ZONE, year: 'numeric', month: '2-digit', day: '2-digit' })
            .formatToParts(date)
            .map((part) => [part.type, part.value]),
    );

    return `${parts.year}-${parts.month}-${parts.day}`;
}

/** Days from the clinic's today to a date, counted on calendar days. */
export function daysUntil(isoDate, now = new Date()) {
    const [from, to] = [clinicDate(now), isoDate].map((value) => Date.UTC(...value.split('-').map((part, index) => Number(part) - (index === 1 ? 1 : 0))));

    return Math.round((to - from) / DAY);
}

/** "Termina hoy", "Termina mañana" or "Quedan N días", on the clinic's calendar. */
export function promotionLeft(endIso, now = new Date()) {
    const days = daysUntil(endIso, now);

    return days <= 0 ? 'Termina hoy' : days === 1 ? 'Termina mañana' : `Quedan ${days} días`;
}

/** How much of the clinic's current day has gone by (0 to 1). */
function clinicDayElapsed(now) {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', { timeZone: CLINIC_TIME_ZONE, hourCycle: 'h23', hour: '2-digit', minute: '2-digit' })
            .formatToParts(now)
            .map((part) => [part.type, Number(part.value)]),
    );

    return clamp((parts.hour * 60 + parts.minute) / 1440);
}

/**
 * How much of a promotion is left, as the width of its bar (4 to 100 %). It runs from the start of its
 * first day to the end of its last one on the clinic's clock, so it goes down from the first day.
 */
export function promotionBar(startIso, endIso, now = new Date()) {
    const total = Math.max(daysUntil(endIso, now) - daysUntil(startIso, now) + 1, 1);
    const left = Math.max(daysUntil(endIso, now) + 1 - clinicDayElapsed(now), 0);

    return Math.max(4, Math.min(100, Math.floor((left / total) * 100)));
}

/**
 * A carousel that advances on its own unless it is held (focus, pointer, off screen) or paused.
 * With `still` (the visitor asked for less motion) it is held for good: only the buttons move it (CA9).
 */
export function createCarousel(count, onChange = () => {}, { still = false } = {}) {
    const holds = new Set(still ? ['motion'] : []);
    let current = 0;
    let paused = false;

    const go = (index) => {
        current = ((index % count) + count) % count;
        onChange(current);

        return current;
    };

    return {
        go,
        next: () => go(current + 1),
        previous: () => go(current - 1),
        /* The progress bar of the current dot ended: advance only if nothing holds it. */
        tick: () => (paused || holds.size > 0 ? current : go(current + 1)),
        hold: (reason) => holds.add(reason),
        release: (reason) => holds.delete(reason),
        togglePause: () => {
            paused = !paused;

            return paused;
        },
        get current() {
            return current;
        },
        get paused() {
            return paused;
        },
        get moving() {
            return !paused && holds.size === 0;
        },
    };
}

/** The element Tab moves the focus to inside a dialog, wrapping at both ends. */
export function trappedFocus(focusables, current, backwards) {
    if (focusables.length === 0) {
        return null;
    }

    const index = focusables.indexOf(current);

    if (backwards) {
        return index <= 0 ? focusables[focusables.length - 1] : focusables[index - 1];
    }

    return index === -1 || index === focusables.length - 1 ? focusables[0] : focusables[index + 1];
}

const NARROW = 600;
const FAN_GAP = 24;
const FAN_STEP = (11 * Math.PI) / 180;

/**
 * Where and how big the fan of photos of the welcome opens. On a wide screen as the proposal does
 * (two thirds down the scene). On a phone the closing text with the fan under it is centred in the
 * scene, with as much room above as below and photos of the same size (refined 2026-11-15):
 * `textShift` moves the text down, `drop` is how far the outer photos fall below the middle one.
 */
export function fanLayout({ width, height, cardWidth, cardHeight, textTop = 0, textBottom = 0, count = 6 }) {
    const radius = width * 0.78;

    if (width > NARROW) {
        return { scale: Math.min(0.56, Math.max(0.32, width / (4.4 * cardWidth))), radius, baseline: height * 0.66, textShift: 0, drop: 0 };
    }

    const scale = Math.min(0.56, Math.max(0.32, width / (4.4 * cardWidth)));
    const drop = (1 - Math.cos(((count - 1) / 2) * FAN_STEP)) * radius * 0.9;
    const textHeight = textBottom - textTop;
    const group = textHeight + FAN_GAP + cardHeight * scale + drop;
    const top = Math.max(textTop, (height - group) / 2);

    return { scale, radius, baseline: top + textHeight + FAN_GAP + (cardHeight * scale) / 2, textShift: top - textTop, drop };
}

function initDeck(doc, win, D) {
    const section = D.ref('VwSec');
    const slot = D.ref('VwSlot');
    const layer = D.ref('DkLayer');

    /* With less motion there is no scene: the welcome stays in its still version (CSS `.still`, CA9). */
    if (!section || !slot || !layer || D.reducedMotion()) {
        return;
    }

    const end = D.ref('DkEnd');
    const copy = D.ref('VwCopy');
    const hint = D.ref('VwHint');
    const cards = [...layer.querySelectorAll('.dk-card')];
    const count = cards.length;

    if (!count) {
        return;
    }

    const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2);
    const smooth = (t) => t * t * (3 - 2 * t);
    const lerp = (a, b, t) => a + (b - a) * t;
    const jitter = [-3, 4, -6, 2.5, -4.5, 5];
    const hovered = cards.map(() => 1);
    let order = cards.map((_, index) => index);
    let fly = null;
    let hover = -1;
    let mouse = { x: 0, y: 0 };
    let opened = 0;
    let lastWidth = 0;
    let lastHeight = 0;

    section.addEventListener('pointermove', (event) => {
        const box = (section.firstElementChild || section).getBoundingClientRect();
        mouse = {
            x: Math.max(-1, Math.min(1, ((event.clientX - box.left) / box.width) * 2 - 1)),
            y: Math.max(-1, Math.min(1, ((event.clientY - box.top) / box.height) * 2 - 1)),
        };
    });
    section.addEventListener('pointerleave', () => {
        mouse = { x: 0, y: 0 };
    });
    cards.forEach((card, index) => {
        card.addEventListener('pointerenter', () => {
            hover = index;
        });
        card.addEventListener('pointerleave', () => {
            hover = -1;
        });
    });
    win.setInterval(() => {
        if (fly || opened > 0.04 || hover >= 0 || doc.hidden) {
            return;
        }

        fly = { card: order[0], start: win.performance.now() };
    }, 4200);

    let last = win.performance.now();

    const loop = (now) => {
        win.requestAnimationFrame(loop);
        const dt = Math.min((now - last) / 1000, 0.05);
        last = now;
        const sticky = section.firstElementChild;
        const box = section.getBoundingClientRect();

        if (box.bottom < -100 || box.top > win.innerHeight + 100) {
            return;
        }

        const span = box.height - sticky.offsetHeight;
        const progress = span > 0 ? clamp(-box.top / span) : 0;
        const e = ease(clamp((progress - 0.05) / 0.7));
        opened = e;
        const stage = sticky.getBoundingClientRect();
        const target = slot.getBoundingClientRect();
        const w0 = target.width;
        const h0 = target.height;

        if (!w0 || !h0) {
            return;
        }

        if (w0 !== lastWidth || h0 !== lastHeight) {
            cards.forEach((card) => {
                card.style.width = `${w0}px`;
                card.style.height = `${h0}px`;
            });
            lastWidth = w0;
            lastHeight = h0;
        }

        const W = stage.width;
        const H = stage.height;
        const cx0 = target.left - stage.left + w0 / 2;
        const cy0 = target.top - stage.top + h0 / 2;
        let f = 0;

        if (fly) {
            f = clamp((now - fly.start) / 1100);

            if (f >= 1) {
                order = order.slice(1).concat(order[0]);
                fly = null;
                f = 0;
            }
        }

        const textTop = end ? end.offsetTop : 0;
        const { scale: fanScale, radius, baseline, textShift } = fanLayout({ width: W, height: H, cardWidth: w0, cardHeight: h0, textTop, textBottom: end ? textTop + end.offsetHeight : 0, count });
        const step = FAN_STEP;

        cards.forEach((el, c) => {
            const k = order.indexOf(c);
            const flying = fly && fly.card === c;
            const kk = flying ? lerp(0, count - 1, smooth(f)) : k;
            const lift = flying ? Math.sin(Math.PI * f) : 0;
            const dx = kk * 16 + mouse.x * kk * 7 + lift * w0 * 0.66;
            const dy = kk * 12 + mouse.y * kk * 5 - lift * 70;
            const dz = -kk * 46 + lift * 140;
            const drz = jitter[c % jitter.length] * (kk === 0 && !flying ? 0.35 : 1) + lift * 14;
            const dry = mouse.x * 12 - lift * 30;
            const drx = -mouse.y * 8;
            const dsc = 1 - Math.min(kk, 4) * 0.03;
            const dop = clamp(4.2 - kk);
            const angle = (c - (count - 1) / 2) * step;
            const fx = W / 2 + Math.sin(angle) * radius;
            const fy = baseline + (1 - Math.cos(angle)) * radius * 0.9;
            const hk = hover === c && e > 0.85 ? 1.14 : 1;
            hovered[c] += (hk - hovered[c]) * (1 - Math.exp(-dt * 10));
            const ec = ease(clamp((e - c * 0.035) / 0.82));
            const x = lerp(cx0 + dx, fx, ec);
            const y = lerp(cy0 + dy, fy - (hovered[c] - 1) * 180, ec);
            const z = lerp(dz, 0, ec);

            el.style.transform = `translate3d(${(x - w0 / 2).toFixed(1)}px,${(y - h0 / 2).toFixed(1)}px,${z.toFixed(1)}px) rotateX(${lerp(drx, 0, ec).toFixed(2)}deg) rotateY(${lerp(dry, 0, ec).toFixed(2)}deg) rotateZ(${lerp(drz, (angle * 180) / Math.PI, ec).toFixed(2)}deg) scale(${lerp(dsc, fanScale * hovered[c], ec).toFixed(3)})`;
            el.style.opacity = lerp(dop, 1, ec).toFixed(3);
            const deckZ = flying ? (f < 0.5 ? 200 : 1) : 100 - k * 10;
            el.style.zIndex = ec > 0.5 ? (hover === c ? 60 : 20 + c) : deckZ;
        });

        if (end) {
            const shown = clamp((e - 0.7) / 0.26);
            end.style.opacity = shown.toFixed(3);
            end.style.transform = `translateX(-50%) translateY(${((1 - shown) * 24 + textShift).toFixed(1)}px)`;
            end.style.pointerEvents = shown > 0.6 ? 'auto' : 'none';
        }

        if (copy) {
            copy.style.opacity = clamp(1 - e * 2.4).toFixed(3);
            copy.style.transform = `translateX(${(-90 * e).toFixed(1)}px)`;
        }

        if (hint) {
            hint.style.opacity = clamp(1 - progress * 8).toFixed(3);
        }
    };

    win.requestAnimationFrame(loop);
}

/** One answer open at a time; the first one opens on load; Escape closes the open one (CA10). */
function initFaq(doc) {
    const items = [...doc.querySelectorAll('.fq-item')];
    const buttonOf = (item) => item.querySelector('.fq-q');
    const setOpen = (open) => {
        items.forEach((item) => {
            const on = item === open;
            item.classList.toggle('on', on);
            buttonOf(item)?.setAttribute('aria-expanded', on ? 'true' : 'false');
            /* A folded answer is out of reach of screen readers and the keyboard. */
            const answer = item.querySelector('.fq-a');

            if (answer) {
                answer.inert = !on;
            }
        });
    };

    items.forEach((item) => {
        const button = buttonOf(item);

        if (!button) {
            return;
        }

        button.addEventListener('click', () => setOpen(item.classList.contains('on') ? null : item));
        item.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && item.classList.contains('on')) {
                setOpen(null);
                button.focus();
            }
        });
    });

    setOpen(items[0] ?? null);
}

/** Shows the current promotion in the coupon and the others as tickets; ordered by the server. */
export function initPromos(root) {
    const tickets = [...root.querySelectorAll('[data-promo]')];
    const card = root.querySelector('[data-pm-card]');
    const field = (name) => root.querySelector(`[data-pm="${name}"]`);

    /* A single promotion has no tickets: its countdown comes from the dates of the coupon (T043). */
    if (!tickets.length) {
        const end = card?.getAttribute('data-end');

        if (end) {
            field('left').textContent = promotionLeft(end);
            field('bar').style.width = `${promotionBar(card.getAttribute('data-start'), end)}%`;
        }

        return;
    }

    let flip = false;

    const show = (index, animate) => {
        const ticket = tickets[index];
        const description = ticket.getAttribute('data-desc');

        field('pct').textContent = ticket.getAttribute('data-pct');
        field('title').textContent = ticket.getAttribute('data-title');
        field('desc').textContent = description;
        field('desc').hidden = !description;
        field('from').textContent = ticket.getAttribute('data-from');
        field('to').textContent = ticket.getAttribute('data-to');
        field('left').textContent = promotionLeft(ticket.getAttribute('data-end'));
        field('bar').style.width = `${promotionBar(ticket.getAttribute('data-start'), ticket.getAttribute('data-end'))}%`;

        if (field('of')) {
            field('of').textContent = `${index + 1} de ${tickets.length} activas`;
        }

        tickets.forEach((other, position) => {
            other.hidden = position === index;
            const left = other.querySelector('[data-left]');

            if (left) {
                left.textContent = promotionLeft(other.getAttribute('data-end'));
            }
        });

        if (animate && card) {
            flip = !flip;
            card.classList.toggle('swA', !flip);
            card.classList.toggle('swB', flip);
        }
    };

    tickets.forEach((ticket, index) => ticket.addEventListener('click', () => show(index, true)));
    show(0, false);
}

/** The viewer of a certificate: focus kept inside, Escape and the backdrop close it (016 CA12, CA13, CA26). */
function initCerts(root, doc) {
    const viewer = root.querySelector('[data-lb]');

    if (!viewer) {
        return;
    }

    const image = viewer.querySelector('[data-lb-img]');
    const seal = viewer.querySelector('[data-lb-seal]');
    const closeButton = viewer.querySelector('[data-lb-close]');
    let opener = null;

    const set = (name, value) => viewer.querySelectorAll(`[data-lb="${name}"]`).forEach((el) => {
        el.textContent = value;
    });
    const focusables = () => [...viewer.querySelectorAll('button, a[href]')].filter((el) => !el.hidden);
    const onKey = (event) => {
        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'Tab') {
            event.preventDefault();
            trappedFocus(focusables(), doc.activeElement, event.shiftKey)?.focus();
        }
    };

    function close() {
        viewer.hidden = true;
        doc.removeEventListener('keydown', onKey);
        opener?.focus();
    }

    function open(button) {
        opener = button;
        ['title', 'inst', 'year', 'date', 'ini'].forEach((name) => set(name, button.getAttribute(`data-${name}`) || ''));
        const source = button.getAttribute('data-img');
        image.hidden = !source;
        seal.hidden = Boolean(source);

        if (source) {
            image.src = source;
            image.alt = `${button.getAttribute('data-title')} — ${button.getAttribute('data-inst')}`;
        }

        viewer.hidden = false;
        doc.addEventListener('keydown', onKey);
        closeButton.focus();
    }

    root.querySelectorAll('[data-cf]').forEach((button) => button.addEventListener('click', () => open(button)));
    closeButton.addEventListener('click', close);
    /* Only a press that starts and ends on the backdrop closes it: a selection that goes too far does not. */
    let pressedOnBackdrop = false;
    viewer.addEventListener('pointerdown', (event) => {
        pressedOnBackdrop = event.target === viewer;
    });
    viewer.addEventListener('click', (event) => {
        if (event.target === viewer && pressedOnBackdrop) {
            close();
        }

        pressedOnBackdrop = false;
    });
}

/** The testimonials: advance on their own, with a pause button, held by focus, pointer or off screen. */
function initTestimonials(root, win, D) {
    const slides = [...root.querySelectorAll('[data-ts-slide]')];
    const dots = [...root.querySelectorAll('[data-ts-dot]')];
    const position = root.querySelector('[data-ts-pos]');
    const pause = root.querySelector('[data-ts-pause]');
    const section = root.closest('section') || root;

    if (slides.length < 2) {
        return;
    }

    let flip = false;

    const carousel = createCarousel(slides.length, (current) => {
        flip = !flip;
        slides.forEach((slide, index) => {
            slide.hidden = index !== current;

            if (index === current) {
                slide.classList.toggle('swA', !flip);
                slide.classList.toggle('swB', flip);
            }
        });
        dots.forEach((dot, index) => {
            dot.classList.toggle('on', index === current);
            dot.classList.toggle('done', index < current);
            const bar = dot.querySelector('i');

            if (bar && index === current) {
                bar.style.animation = 'none';
                void bar.offsetWidth;
                bar.style.animation = '';
            }
        });

        if (position) {
            position.textContent = `${current < 9 ? '0' : ''}${current + 1}`;
        }
    }, { still: D.reducedMotion() });

    const reflect = () => section.classList.toggle('hold', !carousel.moving);

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => carousel.go(index));
        dot.querySelector('i')?.addEventListener('animationend', () => {
            if (index === carousel.current) {
                carousel.tick();
            }
        });
    });
    root.querySelector('[data-ts-prev]')?.addEventListener('click', () => carousel.previous());
    root.querySelector('[data-ts-next]')?.addEventListener('click', () => carousel.next());

    const holdOn = (reason) => () => {
        carousel.hold(reason);
        reflect();
    };
    const holdOff = (reason) => () => {
        carousel.release(reason);
        reflect();
    };

    root.addEventListener('pointerenter', holdOn('pointer'));
    root.addEventListener('pointerleave', holdOff('pointer'));
    root.addEventListener('focusin', holdOn('focus'));
    root.addEventListener('focusout', holdOff('focus'));

    if ('IntersectionObserver' in win) {
        new win.IntersectionObserver(([entry]) => (entry.isIntersecting ? holdOff('screen') : holdOn('screen'))()).observe(section);
    }

    pause?.addEventListener('click', () => {
        const paused = carousel.togglePause();
        pause.setAttribute('aria-pressed', paused ? 'true' : 'false');
        pause.setAttribute('aria-label', paused ? 'Reanudar los testimonios' : 'Pausar los testimonios');
        reflect();
    });
}

/** The strip "Visítanos": open or closed now, on the clinic's clock. */
function initVisit(el, win, D) {
    const update = () => {
        const status = D.clinic();
        const set = (name, value) => {
            const target = el.querySelector(`[data-vis="${name}"]`);

            if (target) {
                target.textContent = value;
            }
        };

        el.classList.toggle('open', status.open);
        el.classList.toggle('closed', !status.open);
        set('text', status.open ? 'Abierto ahora' : 'Cerrado ahora');
        set('sub', status.near);
        set('hours', status.todayHours);
    };

    update();
    win.setInterval(update, 60000);
}

/** The coupon of the phone menu of the home (T042) says how many days are left, as the big one. */
export function fillMenuCoupon(doc, now = new Date()) {
    const coupon = doc.querySelector('[data-menu-promo]');
    const left = coupon?.querySelector('[data-menu-promo-left]');

    if (left) {
        left.textContent = promotionLeft(coupon.getAttribute('data-end'), now);
    }
}

export function initHome(win = window, doc = document) {
    const D = win.Dentissa;

    D.ready(() => {
        initDeck(doc, win, D);
        fillMenuCoupon(doc);
        initFaq(doc);

        const promos = doc.querySelector('[data-promos]');
        const certificates = doc.querySelector('[data-certs]');
        const testimonials = doc.querySelector('[data-ts]');
        const visit = doc.querySelector('[data-vis]');

        if (promos) {
            initPromos(promos);
        }

        if (certificates) {
            initCerts(certificates, doc);
        }

        if (testimonials) {
            initTestimonials(testimonials, win, D);
        }

        if (visit) {
            initVisit(visit, win, D);
        }
    });
}

if (typeof window !== 'undefined' && window.Dentissa) {
    initHome();
}
