import { stateBadge } from '../ui/badge';
import { announce, announceError } from '../ui/status';

/*
 * Inicio del panel (spec 020). Cada sección pide su lectura por separado y se pinta sola: carga,
 * vacío con el texto de la spec y error persistente con "Reintentar", que vuelve a pedir solo esa
 * sección (CA12). Todo dato entra con textContent o clonando plantillas, nunca como HTML (TM7).
 * Las acciones salen del `can` de cada cita; la API vuelve a comprobarlas (P13).
 */
(function () {
    if (window.__dashboardInit) {
        return;
    }

    window.__dashboardInit = true;

    // The dialog to create a patient also opens from here; there is no listing to reload.
    window.patientsPage = {
        hideError: function () {},
        showError: function () {},
        showSuccess: function (message) {
            announce(message);
        },
        reload: function () {
            announce('Paciente creado.');
        },
    };

    const root = document.querySelector('[data-dashboard]');

    if (!root) {
        return;
    }

    const LOAD_ERROR = 'No se pudo cargar esta sección.';
    const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const now = root.dataset.dashboardNow || '';
    const currentMonth = root.dataset.currentMonth || '';

    const section = (name) => root.querySelector(`[data-dashboard-section="${name}"]`);
    const template = (name) => root.querySelector(`[data-dashboard-template="${name}"]`);
    const clone = (name) => template(name).content.firstElementChild.cloneNode(true);

    let nextAppointment = null;

    /*
     * R8: the last load of each read wins. A section can be read again before the previous answer
     * arrives (Reintentar, or the reload after completing or cancelling); an older answer that
     * arrives late is dropped instead of painting over the newer one.
     */
    const latest = {};

    function begin(read) {
        latest[read] = (latest[read] ?? 0) + 1;

        return latest[read];
    }

    const isLatest = (read, ticket) => latest[read] === ticket;

    /** Shows one state of a section (`loading`, `empty`, `error` or `content`) and hides the rest. */
    function show(target, state) {
        if (!target) {
            return;
        }

        target.querySelectorAll('[data-dashboard-state]').forEach((node) => {
            node.hidden = node.dataset.dashboardState !== state;
        });

        const content = target.querySelector('[data-dashboard-content]');

        if (content) {
            content.hidden = state !== 'content';
        }

        if (state !== 'content') {
            target.querySelectorAll('[data-dashboard-more]').forEach((node) => {
                node.hidden = true;
            });
        }
    }

    function text(scope, selector, value) {
        const node = scope.querySelector(selector);

        if (node) {
            node.textContent = value ?? '';
        }
    }

    /** A status badge made of elements, so nothing of the API enters as HTML. */
    function badgeNode(status) {
        const holder = document.createElement('template');
        holder.innerHTML = stateBadge(status);

        return holder.content.firstElementChild;
    }

    function detailOf(appointment) {
        return [appointment.treatment_name, appointment.doctor_name].filter(Boolean).join(' · ');
    }

    /** R10: a repeated action says, for a screen reader, which appointment it acts on. */
    function nameAction(button, appointment) {
        let suffix = button.querySelector('[data-action-for]');

        if (!suffix) {
            suffix = document.createElement('span');
            suffix.className = 'sr-only';
            suffix.dataset.actionFor = '';
            button.appendChild(suffix);
        }

        suffix.textContent = ` de ${appointment.patient_name ?? ''} a las ${appointment.time ?? ''}`;
    }

    async function read(url) {
        const response = await fetch(url, {
            credentials: 'include',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const payload = await response.json();

        return payload.data;
    }

    function failed(names) {
        names.forEach((name) => show(section(name), 'error'));
        announceError(LOAD_ERROR);
    }

    // ---------------------------------------------------------------- today

    function paintNext(data) {
        const target = section('next');
        nextAppointment = data.next;

        const quickRegister = root.querySelector('[data-dashboard-quick="register"]');

        if (quickRegister) {
            quickRegister.hidden = !(data.next && data.next.can && data.next.can.complete);
        }

        if (!target) {
            return;
        }

        if (!data.next) {
            show(target, 'empty');

            return;
        }

        const card = target.querySelector('[data-dashboard-content]');
        text(card, '[data-next-time]', data.next.time);
        text(card, '[data-next-patient]', data.next.patient_name);
        text(card, '[data-next-detail]', detailOf(data.next));

        const status = card.querySelector('[data-next-status]');
        status.replaceChildren(badgeNode(data.next.status));

        const complete = card.querySelector('[data-dashboard-next-complete]');

        if (complete) {
            complete.hidden = !data.next.can.complete;
            nameAction(complete, data.next);
        }

        const detail = card.querySelector('[data-dashboard-next-detail]');

        if (detail) {
            nameAction(detail, data.next);
        }

        const after = card.querySelector('[data-next-after]');
        const names = data.after.map((item) => `${item.time} ${item.patient_name}`);

        if (names.length > 0) {
            let line = 'Después: ' + names.join(', ');

            if (data.after_more > 0) {
                line += ` y ${data.after_more} más`;
            }

            after.textContent = line;
            after.hidden = false;
        } else {
            after.hidden = true;
        }

        show(target, 'content');
    }

    function paintDay(data) {
        const target = section('day');

        if (!target) {
            return;
        }

        const counts = data.counts;

        Object.entries(counts).forEach(([key, value]) => {
            text(target, `[data-day-count="${key}"]`, String(value));
        });

        ['completed', 'pending', 'cancelled'].forEach((key) => {
            const segment = target.querySelector(`[data-day-segment="${key}"]`);

            if (segment) {
                segment.style.width = counts.total > 0 ? `${(counts[key] / counts.total) * 100}%` : '0%';
            }
        });

        show(target, 'content');
    }

    function paintAgenda(data) {
        const target = section('agenda');

        if (!target) {
            return;
        }

        if (data.appointments.length === 0) {
            show(target, 'empty');

            return;
        }

        const list = target.querySelector('[data-dashboard-content]');
        const items = [];
        let nowLinePlaced = false;

        data.appointments.forEach((appointment) => {
            // A41: an overdue next one keeps its mark and the line of now goes after it.
            if (!nowLinePlaced && !appointment.is_past) {
                const line = clone('now-line');
                text(line, '[data-now-time]', data.now || now);
                items.push(line);
                nowLinePlaced = true;
            }

            const item = clone('agenda-item');
            text(item, '[data-item-time]', appointment.time);
            text(item, '[data-item-patient]', appointment.patient_name);
            text(item, '[data-item-detail]', detailOf(appointment));
            item.querySelector('[data-item-status]').replaceChildren(badgeNode(appointment.status));

            if (appointment.is_past) {
                item.dataset.past = '';
            }

            if (appointment.is_next) {
                item.dataset.next = '';
                item.querySelector('[data-item-next-label]').hidden = false;
            }

            items.push(item);
        });

        if (!nowLinePlaced) {
            const line = clone('now-line');
            text(line, '[data-now-time]', data.now || now);
            items.push(line);
        }

        list.replaceChildren(...items);
        show(target, 'content');
    }

    async function loadToday() {
        const ticket = begin('today');
        ['next', 'day', 'agenda'].forEach((name) => show(section(name), 'loading'));

        try {
            const data = await read('/api/v1/agenda/today-summary');

            if (!isLatest('today', ticket)) {
                return;
            }

            paintNext(data);
            paintDay(data);
            paintAgenda(data);
        } catch {
            if (isLatest('today', ticket)) {
                failed(['next', 'day', 'agenda']);
            }
        }
    }

    // ---------------------------------------------------------- pending lists

    function setTotal(name, total) {
        text(root, `[data-dashboard-total="${name}"]`, String(total));
        text(section(name), '[data-dashboard-count]', total > 0 ? String(total) : '');
    }

    /** R9: the summary of the day never claims "0" for a total it does not know. */
    function unknownTotal(name, label) {
        text(root, `[data-dashboard-total="${name}"]`, label);
        text(section(name), '[data-dashboard-count]', '');
    }

    function paintPending(name, data, rowTemplate, moreText) {
        const target = section(name);
        setTotal(name, data.total);

        if (data.items.length === 0) {
            show(target, 'empty');

            return;
        }

        const groups = [];
        let current = null;

        data.items.forEach((appointment) => {
            if (!current || current.label !== appointment.day_label) {
                const group = clone('pending-group');
                text(group, '[data-pending-day]', appointment.day_label);
                current = { label: appointment.day_label, rows: group.querySelector('[data-pending-rows]') };
                groups.push(group);
            }

            const row = clone(rowTemplate);
            text(row, '[data-item-time]', appointment.time);
            text(row, '[data-item-patient]', appointment.patient_name);
            text(row, '[data-item-detail]', detailOf(appointment));

            row.querySelectorAll('button').forEach((button) => {
                button.dataset.appointmentId = appointment.id;
                nameAction(button, appointment);
            });

            const complete = row.querySelector('[data-complete-appointment-trigger]');
            const cancel = row.querySelector('[data-dashboard-cancel]');

            if (complete && !(appointment.can && appointment.can.complete)) {
                complete.remove();
            }

            if (cancel && !(appointment.can && appointment.can.cancel)) {
                cancel.remove();
            } else if (cancel) {
                cancel.dataset.patient = appointment.patient_name ?? '';
                cancel.dataset.date = appointment.date ?? '';
                cancel.dataset.time = appointment.time ?? '';
            }

            current.rows.appendChild(row);
        });

        target.querySelector('[data-dashboard-content]').replaceChildren(...groups);
        show(target, 'content');

        const more = target.querySelector('[data-dashboard-more]');
        const remaining = data.total - data.items.length;

        if (more && remaining > 0) {
            text(more, '[data-dashboard-more-text]', `y ${remaining} más ${moreText}`);
            more.hidden = false;
        }
    }

    async function loadUnclosed() {
        const target = section('unclosed');

        if (!target) {
            return;
        }

        const ticket = begin('unclosed');
        show(target, 'loading');
        unknownTotal('unclosed', '–');

        try {
            const data = await read('/api/v1/agenda/unclosed');

            if (isLatest('unclosed', ticket)) {
                paintPending('unclosed', data, 'unclosed-row', 'sin cerrar');
            }
        } catch {
            if (isLatest('unclosed', ticket)) {
                unknownTotal('unclosed', 'no disponible');
                failed(['unclosed']);
            }
        }
    }

    async function loadWithoutTracking() {
        const target = section('without-tracking');

        if (!target) {
            return;
        }

        const ticket = begin('without-tracking');
        show(target, 'loading');
        unknownTotal('without-tracking', '–');

        try {
            const data = await read('/api/v1/agenda/without-tracking');

            if (isLatest('without-tracking', ticket)) {
                paintPending('without-tracking', data, 'without-tracking-row', 'sin seguimiento');
            }
        } catch {
            if (isLatest('without-tracking', ticket)) {
                unknownTotal('without-tracking', 'no disponible');
                failed(['without-tracking']);
            }
        }
    }

    // ------------------------------------------------------------------ month

    /** "+6", "−2" (U+2212) or "sin cambio" (A10). */
    function difference(value) {
        if (value > 0) {
            return `+${value} respecto a`;
        }

        if (value < 0) {
            return `−${Math.abs(value)} respecto a`;
        }

        return 'sin cambio respecto a';
    }

    function longDate(isoDate) {
        const [year, month, day] = String(isoDate).split('-').map(Number);

        return `${day} de ${MONTHS[month - 1]} de ${year}`;
    }

    function fillFigure(item, value, label, rest) {
        const number = document.createElement('span');
        number.className = 'text-2xl font-bold tabular-nums text-ink';
        number.textContent = String(value);

        const name = document.createElement('span');
        name.className = 'text-sm font-semibold text-ink';
        name.textContent = label;

        const comparison = document.createElement('span');
        comparison.className = 'text-sm text-muted';
        comparison.textContent = rest;

        item.replaceChildren(number, document.createTextNode(' '), name, document.createTextNode(' '), comparison);
    }

    function paintMonth(data) {
        const target = section('month');
        const previous = data.previous_short_label;
        const total = data.appointments.total;
        const fresh = data.patients.new;
        const active = data.patients.active;

        fillFigure(
            target.querySelector('[data-dashboard-month="appointments"]'),
            total.value,
            total.value === 1 ? 'cita' : 'citas',
            `· ${difference(total.difference)} ${previous} (${total.previous})`,
        );
        fillFigure(
            target.querySelector('[data-dashboard-month="new-patients"]'),
            fresh.value,
            fresh.value === 1 ? 'paciente nuevo' : 'pacientes nuevos',
            `· ${difference(fresh.difference)} ${previous} (${fresh.previous})`,
        );
        fillFigure(
            target.querySelector('[data-dashboard-month="active-patients"]'),
            active.value,
            active.value === 1 ? 'paciente activo' : 'pacientes activos',
            `· al ${longDate(active.as_of)}`,
        );

        show(target, 'content');
    }

    async function loadMonth() {
        const target = section('month');

        if (!target || !currentMonth) {
            return;
        }

        const ticket = begin('month');
        show(target, 'loading');

        try {
            const data = await read('/api/v1/reports/monthly?month=' + encodeURIComponent(currentMonth));

            if (isLatest('month', ticket)) {
                paintMonth(data);
            }
        } catch {
            if (isLatest('month', ticket)) {
                failed(['month']);
            }
        }
    }

    // ---------------------------------------------------------------- actions

    const loaders = {
        next: loadToday,
        day: loadToday,
        agenda: loadToday,
        unclosed: loadUnclosed,
        'without-tracking': loadWithoutTracking,
        month: loadMonth,
    };

    function loadAll() {
        return Promise.all([loadToday(), loadUnclosed(), loadWithoutTracking(), loadMonth()]);
    }

    root.addEventListener('click', (event) => {
        const retry = event.target.closest('[data-dashboard-retry]');

        if (retry) {
            const target = retry.closest('[data-dashboard-section]');
            const name = target?.dataset.dashboardSection;

            if (name && loaders[name]) {
                // R7: the button is about to hide; the focus waits on the title of its section and,
                // if the section fails again, goes to its new "Reintentar".
                const title = target.querySelector('h2');

                if (title) {
                    title.setAttribute('tabindex', '-1');
                    title.focus();
                }

                loaders[name]().then(() => {
                    const again = target.querySelector('[data-dashboard-state="error"]:not([hidden]) [data-dashboard-retry]');

                    if (again && (document.activeElement === title || document.activeElement === document.body)) {
                        again.focus();
                    }
                });
            }

            return;
        }

        const detail = event.target.closest('[data-dashboard-next-detail]');

        if (detail && nextAppointment && window.agendaViewAppointmentModal) {
            window.agendaViewAppointmentModal.open(nextAppointment.id, detail);

            return;
        }

        const completeNext = event.target.closest('[data-dashboard-next-complete], [data-dashboard-quick="register"]');

        if (completeNext && nextAppointment && window.agendaCompleteAppointmentModal) {
            // A21: the quick action completes the next appointment, in the dialog "Completar cita".
            window.agendaCompleteAppointmentModal.open(nextAppointment.id, completeNext);

            return;
        }

        const complete = event.target.closest('[data-complete-appointment-trigger]');

        if (complete && window.agendaCompleteAppointmentModal) {
            window.agendaCompleteAppointmentModal.open(complete.dataset.appointmentId, complete);

            return;
        }

        const register = event.target.closest('[data-dashboard-register]');

        if (register && window.agendaCompleteAppointmentModal) {
            window.agendaCompleteAppointmentModal.open(register.dataset.appointmentId, register, { mode: 'register' });

            return;
        }

        const cancel = event.target.closest('[data-dashboard-cancel]');

        if (cancel && window.agendaCancelAppointment) {
            window.agendaCancelAppointment.open({
                id: cancel.dataset.appointmentId,
                patient: cancel.dataset.patient,
                date: cancel.dataset.date,
                time: cancel.dataset.time,
            }, cancel);
        }
    });

    // The dialogs of the agenda reload what they changed through this hook.
    window.agendaPage = {
        reload: loadAll,
    };

    loadAll();
})();
