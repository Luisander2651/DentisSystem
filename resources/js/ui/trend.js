/*
 * Indicador de cambio pintado por el navegador: el mismo marcado que x-ui.trend
 * (docs/design/system.md → Indicador de cambio · spec 019). El color dice si el cambio es bueno o
 * malo para la clínica; siempre con flecha y texto. Todo texto entra con textContent: un dato
 * nunca se interpreta como HTML (TM7).
 */
const PILL = 'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-min font-bold';
const MUTED = 'border-line bg-surface text-muted';

const COLORS = {
    favorable: 'border-transparent bg-success-soft text-success',
    unfavorable: 'border-transparent bg-warning-soft text-warning',
};

const SPOKEN = {
    favorable: ' (favorable)',
    unfavorable: ' (desfavorable)',
};

function span(className, text = '') {
    const element = document.createElement('span');

    if (className) {
        element.className = className;
    }

    element.textContent = text;

    return element;
}

function spoken(text) {
    return span('sr-only', text);
}

function hidden(text) {
    const element = span('', text);
    element.setAttribute('aria-hidden', 'true');

    return element;
}

function number(value, unit) {
    return unit === 'points' ? Math.abs(value).toFixed(1) : String(Math.abs(value));
}

/**
 * El indicador de una cifra comparada con el mes anterior.
 *
 * @param {{trend: string, difference: ?number, unit?: 'count'|'points', current: ?number, previous: ?number, previousShortLabel: string}} figure
 * @returns {HTMLElement}
 */
export function trendElement({ trend, difference, unit = 'count', current, previous, previousShortLabel }) {
    if (current === null && previous !== null) {
        return span('text-min text-muted', `${previousShortLabel}: ${Number(previous).toFixed(1)} %`);
    }

    if (difference === null) {
        const pill = span(`${PILL} ${MUTED}`, `${previousShortLabel}: Sin datos`);
        pill.append(spoken(' (sin comparación)'));

        return pill;
    }

    if (difference === 0) {
        const pill = span(`${PILL} ${MUTED}`);
        pill.append(hidden('='), ' sin cambio', spoken(' (sin cambio)'));

        return pill;
    }

    const pill = span(`${PILL} ${COLORS[trend] ?? MUTED}`);
    const text = `${difference > 0 ? '+' : '−'}${number(difference, unit)}${unit === 'points' ? ' pts' : ''} vs ${previousShortLabel}`;
    pill.append(hidden(difference > 0 ? '▲' : '▼'), ' ' + text);

    if (SPOKEN[trend]) {
        pill.append(spoken(SPOKEN[trend]));
    }

    return pill;
}
