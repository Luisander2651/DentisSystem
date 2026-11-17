/*
 * Public site (spec 026): the tour of the clinic in the gallery, from gallery.js of the proposal.
 * With three or more photos the section stays in place when it reaches the top of the screen and
 * the photos pass sideways while the visitor scrolls down; then the page goes on. The section is
 * as tall as the sideways distance, so any number of photos takes the same scroll per photo.
 * With less motion requested the row keeps scrolling sideways by itself (no pinning).
 * The before-and-after comparator (T031) and its 3D denture (T032) follow the tour.
 */

import { channels } from './tooth3d.js';

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

/* ---------------------------------------------------------------------------------------------
 * The 3D denture of the before and after (T032, CA18), from gallery.js of the revised proposal
 * (2026-11-15): WebGL with no library, drawn on the canvas of the comparator. Left of the line it
 * shows the "before" of the treatment (stains, crooked teeth or a missing one), right of it the
 * "after". Three fixes over the proposal (design.md): the gum comes down almost flat over the
 * missing tooth, the whitened side loses the warm band at the neck of the teeth and the thick
 * teeth at the ends of the arch tuck into the gum. Its colours are uniforms read from the tokens
 * (A30). It draws only in view, at about 30 frames a second and a density of 1.5 at most; with
 * less motion it does not turn. A browser that draws by software, or a phone that cannot keep up
 * (three frames in a row slower than 250 ms), keeps or gets back the flat drawing.
 * ------------------------------------------------------------------------------------------- */

/** The token each colour uniform of the shader takes. */
export const DENTURE_TOKENS = {
    uGum: '--color-gum',
    uGumDeep: '--color-gum-deep',
    uGumGlow: '--color-gum-glow',
    uGumRim: '--color-gum-rim',
    uMouth: '--color-mouth',
    uShadow: '--color-floor-shadow',
    uNeck: '--color-enamel-neck',
    uBody: '--color-enamel-body',
    uTip: '--color-enamel-tip',
    uStain: '--color-enamel-stain',
    uEnamelGlow: '--color-enamel-glow',
    uWhite: '--color-enamel-white',
    uBlush: '--color-blush',
    uTint: '--color-primary-tint-2',
    uPrimary: '--color-primary',
};

/** How far each treatment changes the "before" (stain, crooked, missing) and the "after" (white). */
const TREATMENT_LOOKS = {
    white: { stain: 1, crooked: 0, missing: 0, white: 1 },
    braces: { stain: 0, crooked: 1, missing: 0, white: 0 },
    implant: { stain: 0, crooked: 0, missing: 1, white: 0 },
};
const FRAME_MS = 32;
const MAX_DENSITY = 1.5;
const SLOW_MS = 250;
const SLOW_FRAMES = 3;
const SOFTWARE_RENDERERS = /swiftshader|llvmpipe|softpipe|software|basic render/i;

/** Whether the browser draws WebGL by software (no graphics card), which would freeze the page. */
function drawsBySoftware(gl) {
    const details = gl.getExtension('WEBGL_debug_renderer_info');
    const renderer = details ? gl.getParameter(details.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);

    return SOFTWARE_RENDERERS.test(String(renderer ?? ''));
}

export const ARCH_FS = `precision highp float;
  uniform vec2 uRes;
  uniform float uT;
  uniform vec2 uRot;
  uniform float uSplit;
  uniform vec3 uBefore;
  uniform vec3 uAfter;
  uniform vec3 uGum, uGumDeep, uGumGlow, uGumRim, uMouth, uShadow, uNeck, uBody, uTip, uStain, uEnamelGlow, uWhite, uBlush, uTint, uPrimary;
  float gStain, gCrook, gMiss, gWhite;
  float mT, mTd, mGd, mGy, mLx, mK;
  mat2 r2(float a){float c=cos(a),s=sin(a);return mat2(c,s,-s,c);}
  float smin(float a,float b,float k){float h=clamp(.5+.5*(b-a)/k,0.,1.);return mix(b,a,h)-k*h*(1.-h);}
  float smax(float a,float b,float k){return -smin(-a,-b,k);}
  float sdRB(vec3 p,vec3 b,float r){vec3 q=abs(p)-b;return length(max(q,0.))+min(max(q.x,max(q.y,q.z)),0.)-r;}
  float hash(float n){return fract(sin(n*12.9898+4.1)*43758.5453);}
  float h3(vec3 p){return fract(sin(dot(p,vec3(127.1,311.7,74.7)))*43758.5453);}
  float vnoise(vec3 p){
    vec3 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);
    return mix(mix(mix(h3(i),h3(i+vec3(1,0,0)),f.x),mix(h3(i+vec3(0,1,0)),h3(i+vec3(1,1,0)),f.x),f.y),
               mix(mix(h3(i+vec3(0,0,1)),h3(i+vec3(1,0,1)),f.x),mix(h3(i+vec3(0,1,1)),h3(i+vec3(1,1,1)),f.x),f.y),f.z);
  }
  const float N = 12.;
  const float DT = 0.19;
  float tooth(vec3 q, float i, float upper, float arcHalf, out float tt, out float lxn){
    float k = abs(i - (N - 1.) * .5);
    float h, tn, te, w = arcHalf * .95, cusp = 0., neck = .72, rr0 = .08;
    if (k < 1.) { h = upper > .5 ? .57 : .41; tn = .14; te = .045; if (upper < .5) w *= .92; }
    else if (k < 2.) { h = upper > .5 ? .49 : .43; tn = .13; te = .045; w *= .9; rr0 = .1; }
    else if (k < 3.) { h = upper > .5 ? .56 : .5; tn = .16; te = .08; cusp = 1.; neck = .68; rr0 = .1; }
    else if (k < 5.) { h = upper > .5 ? .42 : .4; tn = .21; te = .19; neck = .8; rr0 = .11; }
    else { h = .36; tn = .15; te = .22; neck = .85; rr0 = .12; }
    float scl = 1.;
    if (upper > .5 && abs(i - 4.) < .5) {
      if (gMiss > .97) { tt = 0.; lxn = 0.; return 1e3; }
      scl = 1. - gMiss;
      q /= scl;
    }
    float hs = hash(i + upper * 31.), hs2 = hash(i * 1.7 + upper * 13.);
    q.xz = r2((hs - .5) * .5 * gCrook) * q.xz;
    q.z += (hs2 - .5) * .1 * gCrook;
    q.y -= (hash(i * 3.1 + upper) - .5) * .07 * gCrook;
    q.xy = r2((hs2 - .5) * .18 * gCrook) * q.xy;
    float t = clamp(q.y / h, 0., 1.);
    float hw = w * mix(neck, 1., smoothstep(0., .5, t));
    float ht = mix(tn, te, smoothstep(.1, 1., t));
    /* lóbulos en la cara visible de incisivos y colmillos */
    if (k < 3.) ht += .007 * cos(q.x / hw * 4.712) * smoothstep(.25, .9, t) * step(0., q.z);
    q.z += q.x * q.x * (1.9 + 2.2 * smoothstep(4.5, 5.5, k)) + (t - .45) * (t - .45) * .12;
    float rr = min(hw * .55, rr0);
    vec2 pc = vec2(q.x, q.y - (h - .12) * .5);
    vec2 hb = vec2(max(hw - rr, .005), (h + .12) * .5 - rr);
    vec2 dq = abs(pc) - hb;
    float d2 = length(max(dq, 0.)) + min(max(dq.x, dq.y), 0.) - rr;
    if (cusp > .5) d2 = smax(d2, (q.y + abs(q.x) * .95 - h) * .75, .035);
    vec2 ww = vec2(d2, abs(q.z) - ht);
    tt = t; lxn = q.x / hw;
    return (min(max(ww.x, ww.y), 0.) + length(max(ww, 0.)) - .025 - .02 * smoothstep(4.5, 5.5, k)) * scl;
  }
  vec2 arch(vec3 p, float Rx, float Rz, float yGum, float upper, out vec4 info){
    float th = atan(p.x / Rx, p.z / Rz);
    float fi = th / DT + (N - 1.) * .5;
    float ic = floor(fi + .5);
    float dt = 1e3, sc = 0., bT = 0., bL = 0., bK = 0.;
    for (int j = 0; j < 3; j++) {
      float i = ic + float(j) - 1.;
      if (i < 0. || i > N - 1.) continue;
      float a = (i - (N - 1.) * .5) * DT;
      vec2 c = vec2(Rx * sin(a), Rz * cos(a));
      vec2 tg = normalize(vec2(Rx * cos(a), -Rz * sin(a)));
      vec2 nm = vec2(-tg.y, tg.x);
      vec2 q2 = p.xz - c;
      float lx = dot(q2, tg);
      float lz = dot(q2, nm);
      float ly = upper > .5 ? (yGum - p.y) : (p.y - yGum);
      float arcHalf = DT * length(vec2(Rx * cos(a), Rz * sin(a))) * .5;
      float tt, ln;
      float d = tooth(vec3(lx, ly, lz), i, upper, arcHalf, tt, ln);
      if (d < dt) { dt = d; bT = tt; bL = ln; bK = abs(i - (N - 1.) * .5); }
      float u = clamp(abs(lx) / (arcHalf * 1.1), 0., 1.);
      float present = (upper > .5 && abs(i - 4.) < .5) ? 1. - gMiss : 1.;
      sc = max(sc, (1. - u * u) * present);
    }
    float e = length(vec2(p.x / Rx, p.z / Rz));
    float rad = (e - 1.) * mix(Rz, Rx, .5);
    float ly = upper > .5 ? (p.y - yGum) : (yGum - p.y);
    float edge = mix(-.05, .085, sc);
    float top = .36;
    /* la encía se adelgaza hacia la base (forma de mandíbula, no de tambor) */
    float taper = .07 * smoothstep(.1, .38, ly);
    float g = sdRB(vec3(0., ly - (edge + top) * .5, rad + .02 + taper), vec3(0., max((top - edge) * .5 - .1, .01), .14), .1);
    /* relieve sobre la raíz de cada diente */
    g -= .03 * sc * (1. - smoothstep(.0, .3, ly - edge));
    g = smax(g, (abs(th) - (N * .5) * DT - .1) * Rx, .08);
    float d = smin(dt, g, .028);
    info = vec4(bT, bL, bK, clamp((ly - edge) / (top - edge), 0., 1.));
    return vec2(d, g < dt ? 1. : 0.);
  }
  /* interior de la boca: se ve oscuro por los huecos entre dientes */
  float cavity(vec3 p){ vec3 q = (p - vec3(0., -.42, -.05)) / vec3(.78, .5, .62); return (length(q) - 1.) * .5; }
  vec2 map(vec3 p){
    vec4 ia, ib;
    vec2 a = arch(p, 1.16, .98, .02, 1., ia);
    vec2 b = arch(p - vec3(0., 0., -.06), 1.04, .86, -.86, 0., ib);
    vec2 r = a.x < b.x ? a : b;
    float cv = cavity(p);
    return cv < r.x ? vec2(cv, 2.) : r;
  }
  /* Igual que map, pero guarda los datos del material en el punto de impacto. */
  vec2 mapInfo(vec3 p){
    vec4 ia, ib;
    vec2 a = arch(p, 1.16, .98, .02, 1., ia);
    vec2 b = arch(p - vec3(0., 0., -.06), 1.04, .86, -.86, 0., ib);
    vec4 inf = a.x < b.x ? ia : ib;
    mT = inf.x; mLx = inf.y; mK = inf.z; mGy = inf.w;
    vec2 r = a.x < b.x ? a : b;
    float cv = cavity(p);
    return cv < r.x ? vec2(cv, 2.) : r;
  }
  vec3 rotv(vec3 v){ v.yz = r2(uRot.y) * v.yz; v.xz = r2(uRot.x) * v.xz; return v; }
  vec3 nrm(vec3 p){ vec2 e = vec2(.0012, -.0012); return normalize(e.xyy * map(p + e.xyy).x + e.yyx * map(p + e.yyx).x + e.yxy * map(p + e.yxy).x + e.xxx * map(p + e.xxx).x); }
  void main(){
    vec2 uv = (gl_FragCoord.xy * 2. - uRes) / uRes.y;
    float xs = gl_FragCoord.x / uRes.x;
    float bw = .03;
    float w = smoothstep(uSplit - bw, uSplit + bw, xs);
    gStain = uBefore.x * (1. - w);
    gCrook = uBefore.y * (1. - w);
    gMiss = uBefore.z * (1. - w);
    gWhite = uAfter.x * w;
    float glow = exp(-pow((xs - uSplit) / bw, 2.));
    vec3 ro = vec3(0., -.38, 4.2);
    float f = 3.1;
    vec3 rd = normalize(vec3(uv, -f));
    vec3 cen = vec3(0., -.42, .45);
    vec3 o = rotv(ro - cen), dir = rotv(rd);
    float t = 2.4, dm = 1e9, tm = t; bool hit = false;
    for (int i = 0; i < 120; i++) {
      vec3 p = o + dir * t + cen;
      float d = map(p).x;
      if (d < dm) { dm = d; tm = t; }
      if (d < .0008) { hit = true; break; }
      t += d * .6;
      if (t > 6.5) break;
    }
    /* sombra de contacto sobre un piso invisible */
    float shA = 0.;
    vec3 oc = o + cen;
    if (dir.y < 0.) {
      float tp = (-1.235 - oc.y) / dir.y;
      vec3 pp = oc + dir * tp;
      float s = length(vec2(pp.x / 1.3, (pp.z - .38) / .78));
      shA = (1. - smoothstep(.45, 1.25, s)) * .28 + (1. - smoothstep(.2, .85, s)) * .16;
    }
    vec3 shC = uShadow;
    float pix = 2. / uRes.y / f;
    float al = hit ? 1. : clamp(1. - dm / (pix * tm * 1.25), 0., 1.);
    if (al <= 0.) { gl_FragColor = vec4(shC * shA, shA); return; }
    float th = hit ? t : tm;
    vec3 p = o + dir * th + cen;
    vec2 mm = mapInfo(p);
    float iT = mT, iLx = mLx, iK = mK, iGy = mGy;
    vec3 n = nrm(p);
    vec3 V = -dir;
    vec3 L1 = rotv(normalize(vec3(.45, .75, .75)));
    vec3 L2 = rotv(normalize(vec3(-.8, -.05, .5)));
    vec3 Lr = rotv(normalize(vec3(-.2, .35, -1.)));
    float ao = 0., sc = 1.;
    for (int i = 1; i < 6; i++) { float h = .012 + .1 * float(i) / 5.; ao += (h - map(p + n * h).x) * sc; sc *= .72; }
    ao = clamp(1. - 2.2 * ao, 0., 1.);
    float thk = map(p - n * .1).x;
    float sss = clamp(1. + thk / .1, 0., 1.);
    float ndv = clamp(dot(n, V), 0., 1.);
    float fr = pow(1. - ndv, 4.);
    vec3 col;
    if (mm.y > 1.5) {
      col = uMouth * (.55 + .45 * clamp(dot(n, L1) * .5 + .5, 0., 1.)) * mix(.5, 1., ao);
    } else if (mm.y > .5) {
      /* encía: más clara junto al diente, más profunda hacia la base, con textura fina */
      vec3 q = p * 30.;
      float n0 = vnoise(q);
      vec3 gn = vec3(vnoise(q + vec3(.35, 0, 0)), vnoise(q + vec3(0, .35, 0)), vnoise(q + vec3(0, 0, .35))) - n0;
      vec3 nG = normalize(n - gn * .1);
      float difG = pow(clamp(dot(nG, L1) * .5 + .5, 0., 1.), 1.6);
      vec3 gTop = uGum, gBase = uGumDeep;
      vec3 gum = mix(gTop, gBase, smoothstep(.05, .95, iGy)) * (.975 + .05 * n0);
      col = gum * (.36 + .72 * difG) * mix(1., ao, .85);
      col += uGumGlow * sss * .16;
      vec3 Hg = normalize(L1 + V);
      col += uBlush * pow(clamp(dot(nG, Hg), 0., 1.), 40.) * .26 * ao;
      col += uGumRim * fr * .22 * ao;
    } else {
      /* esmalte: cálido junto a la encía, claro al centro y translúcido en el borde */
      vec3 cerv = mix(uNeck, uBody, gWhite), body = uBody, tip = uTip;
      vec3 base = mix(cerv, body, smoothstep(.0, .38, iT));
      float trans = smoothstep(.6, 1., iT) * (iK < 2. ? 1. : .3) * (1. - .55 * smoothstep(.0, .5, sss));
      base = mix(base, tip, trans * .85);
      vec3 stained = uStain;
      base = mix(base, stained, gStain * mix(1., .78, iT) * mix(.78, 1., smoothstep(.0, .3, sss)));
      base = mix(base, uWhite, gWhite * .62 * (1. - trans * .5));
      float dif = pow(clamp(dot(n, L1) * .5 + .5, 0., 1.), 1.4);
      col = base * (.32 + .74 * dif) * mix(1., ao, .7);
      col += uEnamelGlow * sss * .1 * (1. - trans);
      vec3 R = reflect(-V, n);
      vec3 env = mix(uBlush, uWhite, smoothstep(-.2, .7, R.y));
      float spw = mix(.5, .22, gStain) * (1. + gWhite * .5);
      vec3 H = normalize(L1 + V);
      col += uWhite * pow(clamp(dot(n, H), 0., 1.), 110.) * spw * 1.4;
      col += uWhite * pow(clamp(dot(n, H), 0., 1.), 22.) * spw * .08;
      col += env * fr * .45 * ao;
      col += uPrimary * .06 * clamp(dot(n, L2), 0., 1.);
      /* surco donde el diente entra en la encía */
      col *= mix(.86, 1., smoothstep(.0, .035, iT));
    }
    /* luz de contorno rosa por detrás */
    col += uGumRim * pow(1. - ndv, 3.) * clamp(dot(n, Lr) * .6 + .4, 0., 1.) * .35 * ao;
    /* brillo suave donde pasa el tratamiento */
    col += uTint * glow * .16;
    col = clamp(col, 0., 1.);
    gl_FragColor = vec4(col * al + shC * shA * (1. - al), al + shA * (1. - al));
  }`;

function startDenture(canvas, state, win, colours, onReady, onSlow) {
    /* A browser that can only draw by software would freeze the page: it keeps the flat drawing. */
    const gl = canvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, failIfMajorPerformanceCaveat: true });

    if (!gl || drawsBySoftware(gl)) {
        return false;
    }

    const compile = (type, source) => {
        const shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);

        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            throw new Error(gl.getShaderInfoLog(shader));
        }

        return shader;
    };
    const program = gl.createProgram();
    gl.attachShader(program, compile(gl.VERTEX_SHADER, 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}'));
    gl.attachShader(program, compile(gl.FRAGMENT_SHADER, ARCH_FS));
    gl.linkProgram(program);

    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        throw new Error('link');
    }

    gl.useProgram(program);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    const position = gl.getAttribLocation(program, 'a');
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
    const uniform = (name) => gl.getUniformLocation(program, name);
    Object.entries(colours).forEach(([name, [red, green, blue]]) => gl.uniform3f(uniform(name), red, green, blue));

    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const look = { ...TREATMENT_LOOKS.white };
    let last = null;
    let start = null;
    let lastDraw = -Infinity;
    let first = true;
    let slowFrames = 0;
    let stopped = false;

    const frame = (now) => {
        if (stopped) {
            return;
        }

        win.requestAnimationFrame(frame);
        start ??= now;
        const seconds = Math.min((now - (last ?? now)) / 1000, 0.05);
        last = now;
        const box = canvas.getBoundingClientRect();

        if (box.bottom < 0 || box.top > win.innerHeight) {
            return;
        }

        const target = TREATMENT_LOOKS[state.mode] ?? TREATMENT_LOOKS.white;
        const ease = 1 - Math.exp(-seconds * 3);
        Object.keys(look).forEach((key) => {
            look[key] += (target[key] - look[key]) * ease;
        });

        if (!first && now - lastDraw < FRAME_MS) {
            return;
        }

        slowFrames = !first && now - lastDraw > SLOW_MS ? slowFrames + 1 : 0;
        lastDraw = now;
        const density = Math.min(win.devicePixelRatio || 1, MAX_DENSITY);
        const width = Math.round(canvas.clientWidth * density);
        const height = Math.round(canvas.clientHeight * density);

        if (!width || !height) {
            return;
        }

        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }

        const elapsed = (now - start) / 1000;
        gl.viewport(0, 0, width, height);
        gl.clearColor(0, 0, 0, 0);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.uniform2f(uniform('uRes'), width, height);
        gl.uniform1f(uniform('uT'), elapsed);
        gl.uniform2f(uniform('uRot'), 0.18 + (reduced ? 0 : Math.sin(elapsed * 0.35) * 0.1), 0.06);
        gl.uniform1f(uniform('uSplit'), state.pos / 100);
        gl.uniform3f(uniform('uBefore'), look.stain, look.crooked, look.missing);
        gl.uniform3f(uniform('uAfter'), look.white, 0, 0);
        gl.drawArrays(gl.TRIANGLES, 0, 3);

        if (first) {
            first = false;
            onReady();
        }

        /* A phone that cannot keep up gets the flat drawing back instead of a frozen page. */
        if (slowFrames >= SLOW_FRAMES) {
            stopped = true;
            onSlow();
        }
    };

    win.requestAnimationFrame(frame);

    return true;
}

/**
 * Draws the denture on the canvas of the comparator in `root`, following its `state` (from
 * initCompare). Returns false, and the flat drawing stays, when the browser cannot draw in 3D or
 * the drawing fails.
 */
export function initDenture(root, state, win = window, doc = document) {
    const canvas = root.querySelector('.ba-cv');
    const flat = root.querySelector('.ba-2d');

    if (!canvas || !state || !win.WebGLRenderingContext) {
        return false;
    }

    const styles = win.getComputedStyle(doc.documentElement);
    const colours = Object.fromEntries(Object.entries(DENTURE_TOKENS).map(([name, token]) => [name, channels(styles.getPropertyValue(token).trim())]));

    try {
        return startDenture(canvas, state, win, colours, () => {
            canvas.classList.add('on');
            flat?.classList.add('off');
        }, () => {
            canvas.classList.remove('on');
            flat?.classList.remove('off');
        });
    } catch {
        return false;
    }
}
