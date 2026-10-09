/*
 * ui-audit · composition at 1440 px (spec 016, CA22).
 *
 * Before any view was touched, `npm run test:ui -- --baseline` stored in baseline-1440.json
 * the skeleton of every screen: its boxes (cards), headings and actions, in the order and the
 * horizontal position they are painted. This check draws the same skeleton again and fails
 * when the sequence of regions, their position or the kinds of action in each one changed.
 *
 * What the skeleton leaves out on purpose, so that it reads the same on any database and
 * survives a change of copy: texts, heading levels, heights and totals. Of a list (siblings
 * with the same tag and the same hooks) only the first element counts.
 *
 * The baseline is frozen: `--baseline` only writes an entry that has none yet or one that
 * `ADMITTED` names, with the acceptance criterion that allows its difference.
 */

export const ADMITTED = [
    {
        criterion: 'CA34',
        entries: ['contenido-galeria', 'contenido-promociones', 'contenido-certificaciones', 'contenido-testimonios'],
        difference: 'Gestión de contenido pierde el encabezado propio de cada pestaña: queda un solo título de página y el nombre de la pestaña pasa a subtítulo junto a su acción.',
    },
    {
        criterion: 'CA17',
        entries: ['agenda', 'agenda-con-citas', 'agenda-dia'],
        difference: 'La tarjeta de cita pierde la rama de botones "ver y editar", que no llegaba a pintarse para el administrador.',
    },
];

const POSITION_TOLERANCE = 2;

/** Runs inside the page: the tree of cards, headings and actions under a root. */
function skeletonInPage(rootSelector) {
    // Always from the top: a step may have scrolled, and a sticky menu changes place with it.
    window.scrollTo(0, 0);

    const viewport = document.documentElement.clientWidth;
    const percent = (pixels) => Math.round((pixels / viewport) * 100);
    const alpha = (color) => {
        const parts = color.match(/[\d.]+/g) ?? [];

        return parts.length === 4 ? Number(parts[3]) : color === 'transparent' ? 0 : 1;
    };

    const isVisible = (element) => {
        const box = element.getBoundingClientRect();
        const style = getComputedStyle(element);

        return box.width > 0 && box.height > 0 && style.visibility !== 'hidden' && style.display !== 'none' && Number(style.opacity) > 0;
    };

    const actionKind = (element) => {
        const tag = element.tagName;

        if (tag === 'A' && element.hasAttribute('href')) {
            return 'link';
        }

        if (tag === 'BUTTON' || tag === 'SUMMARY' || element.getAttribute('role') === 'button') {
            return 'press';
        }

        if (tag === 'SELECT') {
            return 'select';
        }

        if (tag === 'TEXTAREA') {
            return 'field';
        }

        if (tag === 'INPUT') {
            const type = (element.getAttribute('type') ?? 'text').toLowerCase();

            if (type === 'hidden') {
                return null;
            }

            return { checkbox: 'check', radio: 'check', file: 'file', button: 'press', submit: 'press', reset: 'press' }[type] ?? 'field';
        }

        // Something that is pressed without being a control (a calendar day, a gallery card).
        if (getComputedStyle(element).cursor === 'pointer' && getComputedStyle(element.parentElement).cursor !== 'pointer') {
            return 'press';
        }

        return null;
    };

    const isCard = (element) => {
        const box = element.getBoundingClientRect();

        if (box.width < 200 || box.height < 40) {
            return false;
        }

        const style = getComputedStyle(element);
        const sides = ['Top', 'Right', 'Bottom', 'Left'].filter((side) => parseFloat(style[`border${side}Width`]) > 0 && alpha(style[`border${side}Color`]) > 0.1);

        return alpha(style.backgroundColor) > 0.5 || style.backgroundImage !== 'none' || sides.length >= 3 || style.boxShadow !== 'none';
    };

    // The hooks of a subtree: its ids and the names (never the values) of its data attributes.
    const hooks = (element) => {
        const names = new Set();

        for (const node of [element, ...element.querySelectorAll('*')]) {
            if (node.id) {
                names.add(`#${node.id}`);
            }

            for (const attribute of node.attributes) {
                if (attribute.name.startsWith('data-')) {
                    names.add(attribute.name);
                }
            }
        }

        return names;
    };

    // The tags of a subtree down to three levels, to tell two sections from two list items.
    const shape = (element, depth = 0) => (depth === 3
        ? ''
        : [...element.children].map((child) => `${child.tagName}(${shape(child, depth + 1)})`).join(''));

    // Items of one list share most of their hooks (one may lack an optional button); when
    // they carry no hook at all, they must be built alike.
    const isList = (children) => {
        if (children.length < 2 || children.some((child) => child.tagName !== children[0].tagName)) {
            return false;
        }

        const [first, ...rest] = children.map(hooks);

        if (first.size === 0) {
            return rest.every((names) => names.size === 0) && new Set(children.map((child) => shape(child))).size === 1;
        }

        return rest.every((names) => {
            const shared = [...names].filter((name) => first.has(name)).length;

            return shared / (first.size + names.size - shared) >= 0.5;
        });
    };

    const inVisualOrder = (elements) => elements
        .map((element) => ({ element, box: element.getBoundingClientRect() }))
        .sort((a, b) => (Math.abs(a.box.top - b.box.top) > 10 ? a.box.top - b.box.top : a.box.left - b.box.left))
        .map(({ element }) => element);

    const walk = (element) => {
        const box = element.getBoundingClientRect();
        const place = { x: percent(box.left), w: percent(box.width) };
        const kind = actionKind(element);

        if (kind) {
            return [{ k: kind, ...place }];
        }

        if (/^H[1-6]$/.test(element.tagName)) {
            return [{ k: 'heading', ...place }];
        }

        const visible = inVisualOrder([...element.children].filter((child) => !['SCRIPT', 'STYLE', 'TEMPLATE'].includes(child.tagName) && isVisible(child)));
        const children = (isList(visible) ? visible.slice(0, 1) : visible).flatMap(walk);

        return isCard(element) ? [{ k: 'card', ...place, c: children }] : children;
    };

    const root = rootSelector
        ? [...document.querySelectorAll(rootSelector)].find(isVisible)
        : document.body;

    return root ? walk(root) : [];
}

/**
 * The regions of a skeleton, in the order they are painted: every innermost card with the
 * kinds of action it holds, and every heading or action that sits outside a card.
 */
function regions(nodes) {
    const found = [];
    const visit = (node, insideCard) => {
        if (node.k !== 'card') {
            if (!insideCard) {
                found.push({ kind: node.k, x: node.x, w: node.w, actions: '' });
            }

            return;
        }

        if (node.c.some((child) => child.k === 'card')) {
            node.c.forEach((child) => visit(child, false));

            return;
        }

        const actions = node.c.filter((child) => child.k !== 'heading').map((child) => child.k).sort();

        found.push({
            kind: node.c.some((child) => child.k === 'heading') ? 'card+heading' : 'card',
            x: node.x,
            w: node.w,
            actions: actions.join(' '),
        });
    };

    nodes.forEach((node) => visit(node, false));

    return found;
}

const describe = (region) => (region
    ? `${region.kind} en x=${region.x} % con ancho ${region.w} %${region.actions ? ` y acciones [${region.actions}]` : ''}`
    : 'nada');

const sameRegion = (a, b) => a.kind === b.kind
    && a.actions === b.actions
    && Math.abs(a.x - b.x) <= POSITION_TOLERANCE
    && Math.abs(a.w - b.w) <= POSITION_TOLERANCE;

export function capture(page, entry) {
    return page.evaluate(skeletonInPage, entry.dialogSelector ?? null);
}

export async function run(page, entry, { baseline }) {
    if (page.width !== 1440) {
        return [];
    }

    const stored = baseline?.screens?.[entry.id];

    if (!stored) {
        return [{ rule: 'sin línea base', message: 'La entrada no está en baseline-1440.json; ejecuta npm run test:ui -- --baseline' }];
    }

    const expected = regions(stored);
    const actual = regions(await capture(page, entry));

    for (let index = 0; index < Math.max(expected.length, actual.length); index++) {
        if (!expected[index] || !actual[index] || !sameRegion(expected[index], actual[index])) {
            return [{
                rule: 'composición a 1440 px',
                message: `región ${index + 1} de ${expected.length}: se esperaba ${describe(expected[index])} y hay ${describe(actual[index])}`,
            }];
        }
    }

    return [];
}
