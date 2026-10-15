/*
 * Insignia de estado pintada por el navegador: el mismo marcado que x-ui.badge
 * (docs/design/system.md → Insignia de estado). Siempre con texto, nunca solo color.
 */
const TONES = {
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
    info: 'bg-info-soft text-info',
    brand: 'border border-secondary bg-primary-soft text-ink',
    neutral: 'border border-line bg-canvas text-muted',
};

const STATES = {
    asignada: ['Asignada', 'warning'],
    completada: ['Completada', 'success'],
    cancelada: ['Cancelada', 'danger'],
    reprogramada: ['Reprogramada', 'info'],
    active: ['Activo', 'success'],
    inactive: ['Inactivo', 'danger'],
    visible: ['Visible', 'success'],
    oculto: ['Oculto', 'neutral'],
    hidden: ['Oculto', 'neutral'],
};

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
}

/** El marcado de una insignia con el texto y el tono dados. */
export function badge(text, tone = 'neutral') {
    return '<span class="inline-flex min-h-6 items-center rounded-full px-2.5 text-min font-bold ' + (TONES[tone] ?? TONES.neutral) + '">' + escapeHtml(text) + '</span>';
}

/** La insignia de un estado conocido (de cita, de cuenta o de contenido); uno desconocido se pinta neutro con su propio texto. */
export function stateBadge(state) {
    const [text, tone] = STATES[String(state ?? '').toLowerCase()] ?? [String(state ?? ''), 'neutral'];

    return badge(text, tone);
}

/** El texto en español de un estado conocido. */
export function stateLabel(state) {
    return (STATES[String(state ?? '').toLowerCase()] ?? [String(state ?? '')])[0];
}
