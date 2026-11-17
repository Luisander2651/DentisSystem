/*
 * Public site (spec 026): the 3D tour of the home page, from tooth3d.js of the proposal in
 * nueva-landing/. A tooth drawn in WebGL (no library) that changes treatment with the scroll, with
 * particles around it. pages/inicio.js imports it after the `load` event, so it never delays the
 * first paint (CA8).
 *
 * Without WebGL, or when the visitor asked for less motion, no canvas is created and the tour stays
 * in its still version, with the same texts (CA9). No colour is written here: the shader and the
 * particles take theirs from the tokens of the stylesheet when the tour starts (A30).
 */

const VERTEX = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';

/* The colours arrive as uniforms: uEnamel, uEnamelWhite, uEnamelRim, uPrimary, uSecondary,
   uEnvDark, uEnvLight, uMetal and uBlush. */
const FRAGMENT = [
    'precision highp float;',
    'uniform vec2 uRes;uniform float uT;uniform vec2 uRot;uniform float uImp;uniform float uBr;uniform float uWh;uniform float uDist;uniform float uCl;',
    'uniform vec3 uEnamel;uniform vec3 uEnamelWhite;uniform vec3 uEnamelRim;uniform vec3 uPrimary;uniform vec3 uSecondary;uniform vec3 uEnvDark;uniform vec3 uEnvLight;uniform vec3 uMetal;uniform vec3 uBlush;',
    'mat2 r2(float a){float c=cos(a),s=sin(a);return mat2(c,s,-s,c);}',
    'float smin(float a,float b,float k){float h=clamp(.5+.5*(b-a)/k,0.,1.);return mix(b,a,h)-k*h*(1.-h);}',
    'float smax(float a,float b,float k){return -smin(-a,-b,k);}',
    'float sdRB(vec3 p,vec3 b,float r){vec3 q=abs(p)-b;return length(max(q,0.))+min(max(q.x,max(q.y,q.z)),0.)-r;}',
    'float sdRC(vec3 p,vec3 a,vec3 b,float r1,float r2){vec3 ba=b-a;float l2=dot(ba,ba);float rr=r1-r2;float a2=l2-rr*rr;float il2=1./l2;vec3 pa=p-a;float y=dot(pa,ba);float z=y-l2;vec3 xv=pa*l2-ba*y;float x2=dot(xv,xv);float y2=y*y*l2;float z2=z*z*l2;float k=sign(rr)*rr*rr*x2;if(sign(z)*a2*z2>k)return sqrt(x2+z2)*il2-r2;if(sign(y)*a2*y2<k)return sqrt(x2+y2)*il2-r1;return (sqrt(x2*a2*il2)+y*rr)*il2-r1;}',
    'float crownD(vec3 p){vec3 c=p-vec3(0.,.35,0.);c.x*=1.+.06*c.y;float d=sdRB(c,vec3(.4,.2,.34),.28);d=smax(d,-(length(c-vec3(0.,.8,0.))-.34),.14);d=smax(d,-(length(c.xy-vec2(0.,.52))-.06),.08);d=smax(d,-(length(c.zy-vec2(0.,.52))-.06),.08);return d;}',
    'float rootsD(vec3 p){float a=sdRC(p,vec3(-.27,.05,0.),vec3(-.19,-1.12,.06),.3,.07);float b=sdRC(p,vec3(.27,.05,0.),vec3(.21,-1.08,-.05),.3,.07);return smin(a,b,.1);}',
    'float screwD(vec3 p){vec3 s=p+vec3(0.,.55,0.);float ang=atan(s.z,s.x);float rr=.2-max(0.,-s.y-.2)*.32;float th=.034*sin(s.y*36.+ang);float d=max(length(s.xz)-(rr+th),abs(s.y)-.55);float ab=max(length(s.xz)-.15,abs(s.y-.62)-.12);return min(d*.6,ab);}',
    'float brD(vec3 p){vec3 q=p-vec3(0.,.38,.6+.07*uBr-.25*(1.-uBr));float d=sdRB(q,vec3(.12,.09,.04),.03);d=max(d,-sdRB(q-vec3(0.,0.,.06),vec3(.4,.018,.05),0.));vec3 w=p-vec3(0.,.38,.69);float wd=max(length(w.yz)-.018+(1.-uBr)*.3,abs(w.x)-1.3);return min(d,wd);}',
    'float bubD(vec3 p){float d=1e3;for(int i=0;i<7;i++){float fi=float(i);float a=fi*2.4+uT*.3;float y=mod(uT*.22+fi*.31,2.4)-1.3;float r=.95+.18*sin(fi*1.7);vec3 c=vec3(cos(a)*r,y,sin(a)*r);float s=(.05+.035*sin(fi*3.1))*uCl*smoothstep(-1.3,-1.,y)*smoothstep(1.1,.8,y);d=min(d,length(p-c)-s);}return d;}',
    'vec2 map(vec3 p){float cr=crownD(p);float ro=mix(rootsD(p),screwD(p),uImp);float d=smin(cr,ro,mix(.24,.05,uImp));float m=0.;float b=brD(p);if(b<d){d=b;m=1.;}if(uCl>.01){float bu=bubD(p);if(bu<d){d=bu;m=2.;}}return vec2(d,m);}',
    'vec3 rotv(vec3 v){v.yz=r2(uRot.y)*v.yz;v.xz=r2(uRot.x)*v.xz;return v;}',
    'vec3 tr(vec3 p){p=rotv(p);p.y-=.15;return p;}',
    'vec3 nrm(vec3 p){vec2 e=vec2(.0015,-.0015);return normalize(e.xyy*map(p+e.xyy).x+e.yyx*map(p+e.yyx).x+e.yxy*map(p+e.yxy).x+e.xxx*map(p+e.xxx).x);}',
    'void main(){',
    ' vec2 uv=(gl_FragCoord.xy*2.-uRes)/uRes.y;',
    ' vec3 ro=vec3(0.,0.,uDist);float f=2.5;vec3 rd=normalize(vec3(uv,-f));',
    ' float bb=dot(ro,rd);float cc=dot(ro,ro)-3.4;float hh=bb*bb-cc;if(hh<0.){gl_FragColor=vec4(0.);return;}',
    ' float t=max(-bb-sqrt(hh),0.);float tf=-bb+sqrt(hh);float dm=1e9;float tm=t;bool hit=false;',
    ' for(int i=0;i<120;i++){float d=map(tr(ro+rd*t)).x;if(d<dm){dm=d;tm=t;}if(d<.0012){hit=true;break;}t+=d*.85;if(t>tf)break;}',
    ' float pix=2./uRes.y/f;float al=hit?1.:clamp(1.-dm/(pix*tm*1.2),0.,1.);',
    ' if(al<=0.){gl_FragColor=vec4(0.);return;}',
    ' float th=hit?t:tm;vec3 p=tr(ro+rd*th);vec3 n=nrm(p);float mid=map(p).y;',
    ' vec3 V=-rotv(rd);vec3 L1=rotv(normalize(vec3(.55,.8,.75)));vec3 L2=rotv(normalize(vec3(-.9,-.15,.45)));',
    ' float ao=0.;float sc=1.;for(int i=1;i<5;i++){float h=.02+.11*float(i)/4.;ao+=(h-map(p+n*h).x)*sc;sc*=.7;}ao=clamp(1.-2.2*ao,0.,1.);',
    ' float thk=map(p-n*.2).x;float sss=clamp(1.+thk/.2,0.,1.);',
    ' float dif=pow(clamp(dot(n,L1)*.5+.5,0.,1.),1.6);float fr=pow(1.-clamp(dot(n,V),0.,1.),3.);',
    ' vec3 H=normalize(L1+V);float sp=pow(clamp(dot(n,H),0.,1.),90.);',
    ' vec3 base=mix(uEnamel,uEnamelWhite,uWh);',
    ' vec3 col=base*(.3+.78*dif)*mix(1.,ao,.75);',
    ' col+=uPrimary*.2*clamp(dot(n,L2),0.,1.);',
    ' col+=mix(uSecondary,vec3(1.),.4)*sss*.1;',
    ' col+=mix(uSecondary,uEnamelRim,uWh)*fr*.5;',
    ' col+=vec3(1.)*sp*(.55+.7*uWh);',
    ' float mw=max(mid,uImp*smoothstep(-.08,-.2,p.y));',
    ' vec3 R=reflect(-V,n);float e=smoothstep(-.4,.7,R.y);vec3 env=mix(uEnvDark,uEnvLight,e);env+=uPrimary*.25*smoothstep(.2,.9,-R.x);',
    ' vec3 mc=env*uMetal*mix(.6,1.,ao)+vec3(1.)*sp*1.2;',
    ' col=mix(col,mc,clamp(mw,0.,1.));',
    ' if(mid>1.5){col=mix(uBlush,vec3(1.),fr)*(.75+.25*dif)+vec3(1.)*sp*1.5;al*=.85;}',
    ' col+=uWh*fr*vec3(.25)*(.8+.2*sin(uT*3.));',
    ' gl_FragColor=vec4(clamp(col,0.,1.)*al,al);',
    '}',
].join('\n');

/** The tokens each colour uniform of the shader takes. */
export const SHADER_TOKENS = {
    uEnamel: '--color-enamel',
    uEnamelWhite: '--color-enamel-white',
    uEnamelRim: '--color-enamel-rim',
    uPrimary: '--color-primary',
    uSecondary: '--color-secondary',
    uEnvDark: '--color-env-dark',
    uEnvLight: '--color-env-light',
    uMetal: '--color-metal',
    uBlush: '--color-blush',
};

/** The tokens of the particles, repeated by how often each one appears. */
export const PARTICLE_TOKENS = ['--color-primary', '--color-primary', '--color-secondary', '--color-secondary', '--color-primary-deep', '--color-ink'];

/** A hexadecimal token (#rgb or #rrggbb) as the three channels of a shader, from 0 to 1. */
export function channels(hex) {
    const value = hex.trim().replace('#', '');
    const full = value.length === 3 ? [...value].map((digit) => digit + digit).join('') : value;

    return [0, 2, 4].map((start) => Math.round((parseInt(full.slice(start, start + 2), 16) / 255) * 1000) / 1000);
}

/** The colours of the drawing, read from the tokens: `read(name)` gives a token's value. */
export function readPalette(read) {
    const shader = Object.fromEntries(Object.entries(SHADER_TOKENS).map(([uniform, token]) => [uniform, channels(read(token))]));

    return { shader, particles: PARTICLE_TOKENS.map((token) => read(token).trim()) };
}

/** The tooth is drawn only with WebGL and when the visitor did not ask for less motion (CA9). */
export function canDraw(win, reducedMotion) {
    return typeof win.WebGLRenderingContext !== 'undefined' && !reducedMotion;
}

/** The four poses of the tour, one per treatment. */
const POSES = [
    { cl: 1, br: 0, imp: 0, wh: 0, dist: 4.7, yaw: -0.7, pitch: 0.32 },
    { cl: 0, br: 1, imp: 0, wh: 0, dist: 3.6, yaw: 0, pitch: 0.12 },
    { cl: 0, br: 0, imp: 1, wh: 0, dist: 4.5, yaw: 1.7, pitch: 0.18 },
    { cl: 0, br: 0, imp: 0, wh: 1, dist: 4.3, yaw: Math.PI * 2 + 0.4, pitch: 0.26 },
];

/** A renderer of the tooth on a canvas; false when the browser gives no WebGL context. */
function startTooth(win, canvas, palette, pose, onReady) {
    const gl = canvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false });

    if (!gl) {
        return false;
    }

    const shader = (type, source) => {
        const compiled = gl.createShader(type);
        gl.shaderSource(compiled, source);
        gl.compileShader(compiled);

        if (!gl.getShaderParameter(compiled, gl.COMPILE_STATUS)) {
            throw new Error(gl.getShaderInfoLog(compiled));
        }

        return compiled;
    };
    const program = gl.createProgram();
    gl.attachShader(program, shader(gl.VERTEX_SHADER, VERTEX));
    gl.attachShader(program, shader(gl.FRAGMENT_SHADER, FRAGMENT));
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
    Object.entries(palette.shader).forEach(([name, rgb]) => gl.uniform3f(uniform(name), ...rgb));
    const U = Object.fromEntries(['uRes', 'uT', 'uRot', 'uImp', 'uBr', 'uWh', 'uDist', 'uCl'].map((name) => [name, uniform(name)]));

    const state = { imp: 0, br: 0, wh: 0, cl: 1, yaw: -0.5, dist: 5, pitch: 0.24 };
    const start = win.performance.now();
    let last = start;
    let first = true;

    const loop = (now) => {
        win.requestAnimationFrame(loop);
        const dt = Math.min((now - last) / 1000, 0.05);
        last = now;
        const dpr = Math.min(win.devicePixelRatio || 1, 1.75);
        const width = Math.round(canvas.clientWidth * dpr);
        const height = Math.round(canvas.clientHeight * dpr);
        const box = canvas.getBoundingClientRect();

        if (!width || !height || box.bottom < -200 || box.top > win.innerHeight + 200) {
            return;
        }

        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }

        const seconds = (now - start) / 1000;
        const P = pose(dt, seconds, state);
        gl.viewport(0, 0, width, height);
        gl.clearColor(0, 0, 0, 0);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.uniform2f(U.uRes, width, height);
        gl.uniform1f(U.uT, seconds);
        gl.uniform2f(U.uRot, P.yaw, P.pitch);
        gl.uniform1f(U.uImp, P.imp);
        gl.uniform1f(U.uBr, P.br);
        gl.uniform1f(U.uWh, P.wh);
        gl.uniform1f(U.uCl, P.cl);
        gl.uniform1f(U.uDist, P.dist);
        gl.drawArrays(gl.TRIANGLES, 0, 3);

        if (first) {
            first = false;
            onReady();
        }
    };

    win.requestAnimationFrame(loop);

    return true;
}

/** The pose of each frame, from how far the tour has been scrolled; shows the step it is on. */
function tourPose(win, tour, D, view) {
    let step = -1;

    return (dt, seconds, current) => {
        const box = tour.getBoundingClientRect();
        const sticky = tour.firstElementChild;
        const top = sticky ? parseFloat(win.getComputedStyle(sticky).top) || 92 : 92;
        const span = box.height - (sticky ? sticky.offsetHeight : 760);
        const progress = span > 0 ? Math.min(Math.max((top - box.top) / span, 0), 1) : 0;
        const index = Math.min(3, Math.floor(progress * 4));

        if (index !== step) {
            step = index;

            for (let i = 0; i < 4; i++) {
                D.setIf(tour, `ts${i}`, i === index);
            }

            tour.querySelectorAll('.bar i').forEach((mark, i) => mark.classList.toggle('on', i <= index));
        }

        const local = Math.min(1, progress * 4 - index);
        const target = POSES[index];
        const k = 1 - Math.exp(-dt * 3);
        ['cl', 'br', 'imp', 'wh', 'dist', 'pitch'].forEach((key) => {
            current[key] += (target[key] - current[key]) * k;
        });
        current.yaw += (target.yaw + (local - 0.5) * 0.7 + Math.sin(seconds * 0.6) * 0.1 - current.yaw) * k;
        Object.assign(view, { yaw: current.yaw, pitch: current.pitch, dist: current.dist });

        return { ...current };
    };
}

/** The particles around the tooth, behind and in front of it. */
function startParticles(win, back, front, colours, view) {
    const behind = back.getContext('2d');
    const ahead = front.getContext('2d');

    if (!behind || !ahead) {
        return;
    }

    const R = Math.random;
    const particles = [];

    for (let i = 0; i < 230; i++) {
        const band = R() < 0.62;
        particles.push({
            band,
            r: band ? 1.32 + R() * 0.95 : 1.15 + R() * 1.7,
            y: band ? (R() - 0.5) * 0.32 : (R() - 0.5) * 2.8,
            th: R() * Math.PI * 2,
            sp: (band ? 0.28 : 0.07) * (0.5 + R()) * (R() < 0.18 ? -1 : 1),
            s: 0.5 + R() * 1.7,
            c: colours[Math.floor(R() * colours.length)],
            ph: R() * 6.28,
            star: false,
        });
    }

    for (let i = 0; i < 16; i++) {
        particles.push({ band: false, r: 1.3 + R() * 1.2, y: (R() - 0.5) * 2.4, th: R() * 6.28, sp: 0.05 + R() * 0.08, s: 4 + R() * 5, c: R() < 0.6 ? colours[0] : colours[2], ph: R() * 6.28, star: true });
    }

    const star = (ctx, x, y, s) => {
        ctx.beginPath();
        ctx.moveTo(x, y - s);
        ctx.quadraticCurveTo(x + s * 0.12, y - s * 0.12, x + s, y);
        ctx.quadraticCurveTo(x + s * 0.12, y + s * 0.12, x, y + s);
        ctx.quadraticCurveTo(x - s * 0.12, y + s * 0.12, x - s, y);
        ctx.quadraticCurveTo(x - s * 0.12, y - s * 0.12, x, y - s);
        ctx.fill();
    };
    const start = win.performance.now();
    let last = start;

    const loop = (now) => {
        win.requestAnimationFrame(loop);
        const dt = Math.min((now - last) / 1000, 0.05);
        last = now;
        const seconds = (now - start) / 1000;
        const dpr = Math.min(win.devicePixelRatio || 1, 2);
        const W = Math.round(back.clientWidth * dpr);
        const H = Math.round(back.clientHeight * dpr);
        const box = back.getBoundingClientRect();

        if (!W || !H || box.bottom < -200 || box.top > win.innerHeight + 200) {
            return;
        }

        [back, front].forEach((canvas) => {
            if (canvas.width !== W || canvas.height !== H) {
                canvas.width = W;
                canvas.height = H;
            }
        });
        behind.clearRect(0, 0, W, H);
        ahead.clearRect(0, 0, W, H);

        const cy = Math.cos(-view.yaw);
        const sy = Math.sin(-view.yaw);
        const cp = Math.cos(-view.pitch);
        const spitch = Math.sin(-view.pitch);
        const S = 280 * dpr;
        const distance = view.dist || 5;

        for (const p of particles) {
            p.th += p.sp * dt;
            const x = p.r * Math.cos(p.th);
            const z = p.r * Math.sin(p.th);
            const y = p.y + (p.band ? x * 0.2 : 0);
            const x1 = cy * x - sy * z;
            const z1 = sy * x + cy * z;
            const y2 = cp * y - spitch * z1;
            const z2 = spitch * y + cp * z1;
            const depth = distance - z2;

            if (depth < 0.5) {
                continue;
            }

            const k = 2.5 / depth;
            const near = Math.min(Math.max((z2 + 2.6) / 5.2, 0), 1);
            const ctx = z2 >= 0 ? ahead : behind;
            ctx.globalAlpha = (0.22 + 0.7 * near) * (0.65 + 0.35 * Math.sin(seconds * 1.6 + p.ph));
            ctx.fillStyle = p.c;
            const size = p.s * k * 1.9 * dpr;
            const sx = W / 2 + x1 * k * S;
            const sy2 = H / 2 - y2 * k * S;

            if (p.star) {
                star(ctx, sx, sy2, size * (0.75 + 0.25 * Math.sin(seconds * 2.2 + p.ph)));
            } else {
                ctx.beginPath();
                ctx.arc(sx, sy2, size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        behind.globalAlpha = 1;
        ahead.globalAlpha = 1;
    };

    win.requestAnimationFrame(loop);
}

/** A canvas of the tour stage, created only when the tooth can be drawn. */
function canvas(doc, className, label) {
    const element = doc.createElement('canvas');
    element.className = className;

    if (label) {
        element.setAttribute('role', 'img');
        element.setAttribute('aria-label', label);
    } else {
        element.setAttribute('aria-hidden', 'true');
    }

    return element;
}

/**
 * Starts the tour: with WebGL and motion, the tooth and its particles; otherwise the still version
 * (`tour-static`), with every treatment in sight. Returns whether the tooth is drawn.
 */
export function initTooth(win = window, doc = document) {
    const D = win.Dentissa;
    const tour = doc.querySelector('[data-ref="Tour"]');
    const stage = tour?.querySelector('.tour-stage');

    if (!tour || !stage || !D) {
        return false;
    }

    const still = () => {
        tour.classList.add('tour-static');

        return false;
    };

    if (!canDraw(win, D.reducedMotion())) {
        return still();
    }

    const styles = win.getComputedStyle(doc.documentElement);
    const palette = readPalette((token) => styles.getPropertyValue(token));
    const back = canvas(doc, 'pt pt-b');
    const tooth = canvas(doc, 'cv', 'Diente 3D que cambia según el tratamiento');
    const front = canvas(doc, 'pt pt-f');
    const orbitsFront = stage.querySelector('.orbits.front');
    [back, tooth, front].forEach((element) => stage.insertBefore(element, orbitsFront));

    const view = { yaw: -0.7, pitch: 0.3, dist: 4.7 };
    let drawn = false;

    try {
        drawn = startTooth(win, tooth, palette, tourPose(win, tour, D, view), () => tooth.classList.add('on'));
    } catch {
        drawn = false;
    }

    if (!drawn) {
        [back, tooth, front].forEach((element) => element.remove());

        return still();
    }

    tour.classList.add('tour-live');
    startParticles(win, back, front, palette.particles, view);

    return true;
}
