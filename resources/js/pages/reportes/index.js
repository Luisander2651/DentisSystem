/*
 * Spec 019 · Reportes (design.md, composición C). Pide el reporte del mes elegido a la API y lo
 * pinta: cifras con su indicador de cambio, la semana, los tratamientos y la carga del staff. Las
 * cifras, diferencias y tendencias llegan calculadas (P13); aquí solo se presentan. Todo dato entra
 * con textContent o clonando plantillas, nunca como HTML (TM7).
 */
import { announce, announceError, announceFailure, clearAnnouncements } from '../../ui/status';
import { trendElement } from '../../ui/trend';

const LOAD_ERROR = 'No se pudo cargar el reporte. No se perdió ningún dato. Revisa tu conexión e inténtalo de nuevo.';
const LOADING = 'Cargando el reporte…';
const PREPARING = 'Preparando el archivo…';

/** CA14: el texto propio del límite de descargas; nunca el que responda la API (design.md, D14). */
const OWN_FAILURES = { busy: 'Demasiadas descargas. Espera un momento e inténtalo de nuevo.' };

const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

/** Las cifras del mes, en el orden de lectura de design.md. */
const FIGURES = [
    { label: 'Citas del mes', group: 'appointments', key: 'total', wide: true },
    { label: 'Completadas', group: 'appointments', key: 'completed' },
    { label: 'Canceladas', group: 'appointments', key: 'cancelled' },
    { label: 'Sin cerrar', group: 'appointments', key: 'unclosed' },
    { label: 'Pacientes nuevos', group: 'patients', key: 'new' },
    { label: 'Reprogramadas pendientes', group: 'appointments', key: 'rescheduled_pending' },
    { label: 'Asignadas pendientes', group: 'appointments', key: 'assigned_pending' },
    { label: 'Asistencia', group: 'rates', key: 'attendance', rate: true },
    { label: 'Cancelación', group: 'rates', key: 'cancellation', rate: true },
];

/** Intensidad de una casilla de la semana: 0, 1, 2 y 3 o más (design.md → Semana del mes). */
const INTENSITY = [
    'border border-line bg-surface text-muted',
    'bg-primary/25 text-ink',
    'bg-primary/55 text-ink',
    'bg-primary text-ink',
];

(function () {
    const root = document.querySelector('[data-reports-root]');

    if (!root || window.__reportsIndexInit) {
        return;
    }

    window.__reportsIndexInit = true;

    const monthSelect = root.querySelector('[data-reports-month]');
    const downloadButton = root.querySelector('[data-reports-download]');
    const downloadSpinner = root.querySelector('[data-reports-download-spinner]');
    const notice = root.querySelector('[data-reports-current-notice]');
    const loadError = root.querySelector('[data-reports-load-error]');
    const retryButton = root.querySelector('[data-reports-retry]');
    const content = root.querySelector('[data-reports-content]');
    const figuresTitle = root.querySelector('[data-reports-figures-title]');
    const figures = root.querySelector('[data-reports-figures]');
    const figuresNote = root.querySelector('[data-reports-figures-note]');
    const week = root.querySelector('[data-reports-week]');
    const weekCaption = root.querySelector('[data-reports-week-caption]');
    const weekBody = root.querySelector('[data-reports-week-body]');
    const weekFoot = root.querySelector('[data-reports-week-foot]');
    const weekEmpty = root.querySelector('[data-reports-week-empty]');
    const weekLegend = root.querySelector('[data-reports-week-legend]');
    const treatments = root.querySelector('[data-reports-treatments]');
    const treatmentsEmpty = root.querySelector('[data-reports-treatments-empty]');
    const staff = root.querySelector('[data-reports-staff]');
    const staffEmpty = root.querySelector('[data-reports-staff-empty]');
    const statTemplate = root.querySelector('[data-reports-stat-template]');
    const rankTemplate = root.querySelector('[data-reports-rank-template]');

    let latestRequest = 0;

    function capitalize(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    /** "2026-10-19" → "19/10/2026" */
    function shortDate(isoDate) {
        const [year, month, day] = isoDate.split('-');

        return `${day}/${month}/${year}`;
    }

    /** "2026-10-19" → "19 de octubre de 2026" */
    function longDate(isoDate) {
        const [year, month, day] = isoDate.split('-').map(Number);

        return `${day} de ${MONTHS[month - 1]} de ${year}`;
    }

    function citas(count) {
        return count === 1 ? '1 cita' : `${count} citas`;
    }

    function stat(label, figure, wide) {
        const element = statTemplate.content.firstElementChild.cloneNode(true);
        element.querySelector('dt').textContent = label;
        element.querySelector('[data-stat-figure]').textContent = figure;

        if (wide) {
            element.classList.add('col-span-2');
        }

        return element;
    }

    function renderFigures(report) {
        figuresTitle.textContent = `${capitalize(report.label)} en cifras`;
        figures.replaceChildren();

        FIGURES.forEach((figure) => {
            const data = report[figure.group][figure.key];
            const shown = figure.rate ? (data.value === null ? 'Sin datos' : `${data.value.toFixed(1)} %`) : String(data.value);
            const element = stat(figure.label, shown, figure.wide === true);

            element.querySelector('[data-stat-trend]').replaceWith(trendElement({
                trend: data.trend,
                difference: data.difference,
                unit: figure.rate ? 'points' : 'count',
                current: data.value,
                previous: data.previous,
                previousShortLabel: report.previous_short_label,
            }));

            figures.append(element);
        });

        const active = stat('Pacientes activos', String(report.patients.active.value), true);
        const asOf = document.createElement('span');
        asOf.className = 'text-min text-muted';
        asOf.textContent = `al ${longDate(report.patients.active.as_of)}`;
        active.querySelector('[data-stat-trend]').replaceWith(asOf);
        figures.append(active);

        const attendance = report.rates.attendance;
        figuresNote.textContent = '"Sin cerrar": fecha ya pasada y siguen asignadas o reprogramadas.'
            + (attendance.denominator > 0 ? ` Asistencia: ${attendance.numerator} de ${citas(attendance.denominator)} con fecha pasada.` : '');
    }

    function cell(tag, text, className) {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;

        return element;
    }

    /** Un total; el más alto lleva contorno y lo dice también al lector de pantalla (R6). */
    function sumCell(value, isTop) {
        const total = cell('td', String(value), 'h-10 rounded-control text-sm font-bold text-ink' + (isTop ? ' ring-2 ring-inset ring-ink' : ''));

        if (isTop) {
            const spoken = document.createElement('span');
            spoken.className = 'sr-only';
            spoken.textContent = ' (más citas)';
            total.append(spoken);
        }

        return total;
    }

    function renderWeek(report) {
        const demand = report.demand;
        const empty = demand.hours.length === 0;

        weekCaption.textContent = `Citas no canceladas de ${report.label} por hora de inicio y día de la semana, con totales por hora y por día`;
        weekBody.replaceChildren();
        weekFoot.replaceChildren();
        week.hidden = empty;
        weekLegend.hidden = empty;
        weekEmpty.hidden = !empty;
        weekEmpty.textContent = empty ? `No hubo citas en ${report.label}.` : '';

        if (empty) {
            return;
        }

        demand.hours.forEach((hour, index) => {
            const row = document.createElement('tr');
            row.append(cell('th', `${hour}:00`, 'pr-2 text-right text-min font-bold text-muted'));
            row.firstElementChild.scope = 'row';

            demand.matrix[index].forEach((count) => {
                row.append(cell('td', String(count), 'h-10 rounded-control text-sm font-bold ' + INTENSITY[Math.min(count, 3)]));
            });

            row.append(sumCell(demand.by_hour[index], demand.top_hours.includes(hour)));
            weekBody.append(row);
        });

        const total = document.createElement('tr');
        total.append(cell('th', 'Total', 'pr-2 text-right text-min font-bold text-muted'));
        total.firstElementChild.scope = 'row';
        demand.by_weekday.forEach((count, day) => total.append(sumCell(count, demand.top_weekdays.includes(day))));
        total.append(sumCell(demand.total, false));
        weekFoot.append(total);
    }

    function renderRanking(list, emptyText, items, label, value) {
        const highest = Math.max(0, ...items.map((item) => item.appointments));

        list.replaceChildren();
        list.hidden = items.length === 0;
        emptyText.hidden = items.length > 0;

        items.forEach((item) => {
            const line = rankTemplate.content.firstElementChild.cloneNode(true);
            const name = line.querySelector('[data-rank-name]');
            name.textContent = label(item);

            if (item.is_top) {
                const top = document.createElement('span');
                top.className = 'font-normal text-muted';
                top.textContent = ' · más citas';
                name.append(top);
            }

            line.querySelector('[data-rank-value]').textContent = value(item);
            line.querySelector('[data-rank-bar]').style.width = `${highest > 0 ? (item.appointments / highest) * 100 : 0}%`;
            list.append(line);
        });
    }

    function render(report) {
        notice.hidden = !report.is_current;
        notice.textContent = report.is_current ? `Mes en curso: datos hasta hoy, ${shortDate(report.data_until)}.` : '';

        renderFigures(report);
        renderWeek(report);

        treatmentsEmpty.textContent = `No hubo citas en ${report.label}.`;
        renderRanking(treatments, treatmentsEmpty, report.treatments, (item) => item.name, (item) => citas(item.appointments));

        staffEmpty.textContent = `No hubo citas en ${report.label}.`;
        renderRanking(staff, staffEmpty, report.staff, (item) => item.name, (item) => `${citas(item.appointments)} · ${item.completed} ${item.completed === 1 ? 'completada' : 'completadas'}`);
    }

    function showLoadError(status, payload) {
        content.hidden = true;
        loadError.hidden = false;

        if (status === 0 || status >= 500) {
            announceError(LOAD_ERROR);
        } else {
            announceFailure(status, payload);
        }
    }

    async function load(month) {
        const request = ++latestRequest;

        loadError.hidden = true;
        announce(LOADING);

        let response;

        try {
            response = await fetch(`/api/v1/reports/monthly?month=${encodeURIComponent(month)}`, {
                credentials: 'include',
                headers: { Accept: 'application/json' },
            });
        } catch {
            if (request === latestRequest) {
                showLoadError(0, null);
            }

            return;
        }

        const payload = await response.json().catch(() => null);

        if (request !== latestRequest) {
            return;
        }

        if (!response.ok || !payload || !payload.data) {
            showLoadError(response.status, payload);

            return;
        }

        render(payload.data);
        content.hidden = false;
        clearAnnouncements();
    }

    function chosenMonth() {
        return monthSelect.value;
    }

    monthSelect.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('mes', chosenMonth());
        window.history.replaceState(null, '', url);
        load(chosenMonth());
    });

    /* R5: el bloque que se oculta contiene el botón; el foco vuelve al selector del mes antes. */
    retryButton.addEventListener('click', () => {
        monthSelect.focus();
        load(chosenMonth());
    });

    /** The name the server gives the file (Content-Disposition), or the one it would give. */
    function fileName(response, month) {
        const disposition = response.headers.get('Content-Disposition') || '';
        const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);

        const fallback = `reporte-dentissa-${month}.xlsx`;

        if (!match) {
            return fallback;
        }

        try {
            return decodeURIComponent(match[1]);
        } catch {
            return fallback;
        }
    }

    function setDownloading(downloading) {
        downloadButton.setAttribute('aria-disabled', downloading ? 'true' : 'false');
        downloadSpinner.classList.toggle('hidden', !downloading);
    }

    /*
     * CA8 and D5: the file is asked for with fetch and saved from a Blob, so a refusal (429, 403,
     * 422) is announced in the status region instead of opening a JSON in a tab.
     */
    async function download() {
        if (downloadButton.getAttribute('aria-disabled') === 'true') {
            return;
        }

        const month = chosenMonth();
        setDownloading(true);
        announce(PREPARING);

        try {
            const response = await fetch(`/api/v1/reports/monthly/excel?month=${encodeURIComponent(month)}`, {
                credentials: 'include',
                headers: { Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json' },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                announceFailure(response.status, payload, OWN_FAILURES);

                return;
            }

            const blob = await response.blob();
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = fileName(response, month);
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            clearAnnouncements();
        } catch {
            announceFailure(0, null, OWN_FAILURES);
        } finally {
            setDownloading(false);
        }
    }

    downloadButton.addEventListener('click', download);

    load(chosenMonth());
})();
