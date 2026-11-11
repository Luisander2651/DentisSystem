import { stateBadge } from './badge';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function toDisplay(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    const text = String(value).trim();
    return text === '' ? '-' : text;
}

function normalizeStatus(status) {
    const normalized = String(status || '').trim().toLowerCase();
    const mapping = {
        pending: 'asignada',
        confirmed: 'asignada',
        assigned: 'asignada',
        completed: 'completada',
        cancelled: 'cancelada',
        cancelada: 'cancelada',
        completada: 'completada',
        asignada: 'asignada',
        reprogramada: 'reprogramada',
    };

    return mapping[normalized] || 'asignada';
}

/**
 * The hour as the clinical record shows it: "10:00 AM".
 */
export function twelveHourTime(time) {
    const parts = String(time || '').split(':');
    if (parts.length < 2) return toDisplay(time);

    const hour = parseInt(parts[0], 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';

    return (hour % 12 || 12) + ':' + parts[1] + ' ' + ampm;
}

/**
 * Draws the history of appointments of components/records/appointments-history-table (spec 016,
 * CA27; shared with the patient portal since spec 025): one card per appointment below md and a
 * table row from md, newest first, each with its status badge and a "Ver" button. Every value is
 * escaped. The formats of the date and the hour are given by whoever calls it; by default, those
 * of the clinical record.
 */
export function renderAppointmentsHistory({
    tbody,
    cards,
    appointments,
    emptyText,
    formatDate = toDisplay,
    formatTime = twelveHourTime,
    viewAttribute = 'data-record-view-appointment',
}) {
    if (!tbody) return;

    const list = Array.isArray(appointments) ? appointments.slice() : [];

    if (!list.length) {
        if (cards) cards.innerHTML = '';
        tbody.innerHTML = '<tr class="border-t border-line"><td colspan="6" class="px-4 py-6 text-center text-sm text-muted">' + escapeHtml(emptyText) + '</td></tr>';
        return;
    }

    list.sort(function (a, b) {
        const aKey = String(a.date || '') + ' ' + String(a.time || '');
        const bKey = String(b.date || '') + ' ' + String(b.time || '');
        return bKey.localeCompare(aKey);
    });

    const dateOf = (appointment) => toDisplay(formatDate(appointment.date));
    const timeOf = (appointment) => toDisplay(formatTime(appointment.time));
    const viewButtonHtml = function (appointment) {
        return '<button type="button" ' + viewAttribute + ' data-appointment-id="' + escapeHtml(appointment.id) + '" class="inline-flex min-h-control items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Ver<span class="sr-only"> la cita del ' + escapeHtml(dateOf(appointment)) + '</span></button>';
    };

    if (cards) {
        cards.innerHTML = list.map(function (appointment) {
            return [
                '<li class="rounded-box border border-line bg-surface p-3">',
                '<div class="flex items-start justify-between gap-3">',
                '<p class="text-sm font-semibold text-ink">' + escapeHtml(dateOf(appointment)) + ' · ' + escapeHtml(timeOf(appointment)) + '</p>',
                stateBadge(normalizeStatus(appointment.status)),
                '</div>',
                '<p class="mt-1 text-sm text-ink wrap-anywhere">' + escapeHtml(toDisplay(appointment.treatment_name)) + '</p>',
                '<p class="text-sm text-muted wrap-anywhere">' + escapeHtml(toDisplay(appointment.user_name ?? appointment.doctor_name)) + '</p>',
                '<div class="mt-3">' + viewButtonHtml(appointment) + '</div>',
                '</li>',
            ].join('');
        }).join('');
    }

    tbody.innerHTML = list.map(function (appointment) {
        return [
            '<tr class="border-t border-line">',
            '<td class="px-4 py-3 align-top text-ink">' + escapeHtml(dateOf(appointment)) + '</td>',
            '<td class="px-4 py-3 align-top text-ink">' + escapeHtml(timeOf(appointment)) + '</td>',
            '<td class="px-4 py-3 align-top text-ink">' + escapeHtml(toDisplay(appointment.treatment_name)) + '</td>',
            '<td class="px-4 py-3 align-top text-ink">' + escapeHtml(toDisplay(appointment.user_name ?? appointment.doctor_name)) + '</td>',
            '<td class="px-4 py-3 align-top">' + stateBadge(normalizeStatus(appointment.status)) + '</td>',
            '<td class="px-4 py-3 align-top text-ink">' + viewButtonHtml(appointment) + '</td>',
            '</tr>',
        ].join('');
    }).join('');
}
