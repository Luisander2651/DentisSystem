import { stateBadge } from '../../ui/badge';
import { isDialogOpen, openDialog } from '../../ui/dialog';
import { announceError, failureMessage } from '../../ui/status';

(function () {
    if (window.__agendaIndexInit) {
        return;
    }

    window.__agendaIndexInit = true;

    // State
    let currentDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    let appointments = [];
    let appointmentsByDate = new Map();
    let appointmentsToday = [];
    let appointmentStats = {
        today: 0,
        pending: 0,
        confirmed: 0,
        completed: 0,
    };
    let selectedStatus = '';
    let selectedDate = null;

    // Selectors
    const calendarGrid = document.getElementById('calendar-grid');
    const monthDisplay = document.getElementById('current-month-display');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const todayAppointmentsContainer = document.getElementById('today-appointments-container');
    const statusFilter = document.querySelector('[data-agenda-status-filter]');
    const dayTitle = document.querySelector('[data-agenda-day-title]');
    const dayList = document.querySelector('[data-agenda-day-list]');

    // Stats Selectors
    const statToday = document.querySelector('[data-stat-today]');
    const statPending = document.querySelector('[data-stat-pending]');
    const statConfirmed = document.querySelector('[data-stat-confirmed]');
    const statCompleted = document.querySelector('[data-stat-completed]');

    // Modal Selectors
    const dayDetailsModal = document.getElementById('day-details-modal');
    const modalDateDisplay = document.getElementById('modal-date-display');
    const modalAppointmentsList = document.getElementById('modal-appointments-list');

    const isAdminUser = document.querySelector('[data-agenda-is-admin]')?.getAttribute('data-agenda-is-admin') === 'true';

    // Desde este ancho las citas de un día se abren en su diálogo; por debajo, en la lista bajo el calendario.
    const dialogFromHere = window.matchMedia('(min-width: 768px)');

    const secondaryButton = 'inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas';
    const primaryButton = 'inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-transparent bg-primary px-4 text-sm font-semibold text-ink transition-colors hover:bg-primary-hover';

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    }

    function toLocalDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function fromLocalDateString(value) {
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function longDate(date) {
        return date.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function capitalize(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function shortTime(time) {
        return String(time || '').substring(0, 5);
    }

    function countLabel(count) {
        return count === 0 ? 'sin citas' : count === 1 ? '1 cita' : `${count} citas`;
    }

    const monthNames = [
        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];

    async function fetchAppointments() {
        try {
            const params = new URLSearchParams();

            if (selectedStatus) {
                params.set('status', selectedStatus);
            }

            const response = await window.axios.get('/api/v1/appointments' + (params.toString() ? '?' + params.toString() : ''));
            appointments = response.data.data || [];
            buildAppointmentIndexes();
            updateStats();
            renderCalendar();
            renderTodayAppointments();
            renderSelectedDay();

            if (window.agendaAppointmentModal && typeof window.agendaAppointmentModal.refreshAvailability === 'function') {
                window.agendaAppointmentModal.refreshAvailability();
            }
        } catch (error) {
            announceError(failureMessage(error.response ? error.response.status : 0, error.response ? error.response.data : null));
        }
    }

    function buildAppointmentIndexes() {
        appointmentsByDate = new Map();
        appointmentsToday = [];
        appointmentStats = {
            today: 0,
            pending: 0,
            confirmed: 0,
            completed: 0,
        };

        const todayStr = toLocalDateString(new Date());

        for (const appointment of appointments) {
            const dateKey = String(appointment.date || '');
            const bucket = appointmentsByDate.get(dateKey) || [];
            bucket.push(appointment);
            appointmentsByDate.set(dateKey, bucket);

            const normalizedStatus = normalizeAppointmentStatus(appointment.status);

            if (normalizedStatus === 'asignada') {
                appointmentStats.pending += 1;
            } else if (normalizedStatus === 'reprogramada') {
                appointmentStats.confirmed += 1;
            } else if (normalizedStatus === 'completada') {
                appointmentStats.completed += 1;
            }

            if (dateKey === todayStr) {
                appointmentsToday.push(appointment);
            }
        }

        appointmentStats.today = appointmentsToday.length;
    }

    function normalizeAppointmentStatus(status) {
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

    function updateStats() {
        if (statToday) statToday.textContent = appointmentStats.today;
        if (statPending) statPending.textContent = appointmentStats.pending;
        if (statConfirmed) statConfirmed.textContent = appointmentStats.confirmed;
        if (statCompleted) statCompleted.textContent = appointmentStats.completed;
    }

    function renderAppointmentList(container, list, emptyText) {
        if (!container) return;

        container.innerHTML = '';

        if (list.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-sm text-ink';
            empty.textContent = emptyText;
            container.appendChild(empty);
            return;
        }

        list.forEach(appt => {
            container.appendChild(createAppointmentCard(appt));
        });
    }

    function renderTodayAppointments() {
        renderAppointmentList(todayAppointmentsContainer, appointmentsToday, 'No hay citas para hoy.');
    }

    function createAppointmentCard(appt) {
        const article = document.createElement('article');
        article.className = 'rounded-box border border-line bg-surface p-3';
        article.setAttribute('data-appointment-id', appt.id);

        const patient = appt.patient_name || 'Paciente';
        const normalizedStatus = normalizeAppointmentStatus(appt.status);
        const isActionable = normalizedStatus === 'asignada' || normalizedStatus === 'reprogramada';
        const suffix = `<span class="sr-only"> la cita de ${escapeHtml(patient)} de las ${escapeHtml(shortTime(appt.time))}</span>`;

        article.innerHTML = `
            <div class="flex items-start gap-3">
                <p class="shrink-0 text-sm font-bold text-ink">${escapeHtml(shortTime(appt.time))}</p>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-ink wrap-anywhere">${escapeHtml(patient)}</h3>
                    <p class="text-sm text-muted wrap-anywhere">${escapeHtml(appt.treatment_name || 'Consulta')}</p>
                </div>
                ${stateBadge(normalizedStatus)}
            </div>
            <div class="mt-3 flex gap-2">
                ${isActionable && isAdminUser ? `<button type="button" class="${primaryButton}" data-complete-appointment-trigger data-appointment-id="${escapeHtml(appt.id)}">Completar${suffix}</button>` : ''}
                <button type="button" class="${secondaryButton}" data-view-appointment-trigger data-appointment-id="${escapeHtml(appt.id)}">Ver${suffix}</button>
            </div>
        `;

        const completeButton = article.querySelector('[data-complete-appointment-trigger]');
        if (completeButton) {
            completeButton.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.agendaCompleteAppointmentModal && typeof window.agendaCompleteAppointmentModal.open === 'function') {
                    window.agendaCompleteAppointmentModal.open(appt.id, completeButton);
                }
            });
        }

        const viewButton = article.querySelector('[data-view-appointment-trigger]');
        if (viewButton) {
            viewButton.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.agendaViewAppointmentModal && typeof window.agendaViewAppointmentModal.open === 'function') {
                    window.agendaViewAppointmentModal.open(appt.id, viewButton);
                }
            });
        }

        return article;
    }

    function renderCalendar() {
        if (!calendarGrid || !monthDisplay) return;

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthDisplay.textContent = `${monthNames[month]} ${year}`;
        calendarGrid.innerHTML = '';

        let firstDay = new Date(year, month, 1).getDay();
        firstDay = firstDay === 0 ? 6 : firstDay - 1; // Mon = 0

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        for (let i = firstDay; i > 0; i--) {
            calendarGrid.appendChild(createDayElement(daysInPrevMonth - i + 1, year, month - 1, true));
        }

        const today = new Date();
        for (let i = 1; i <= daysInMonth; i++) {
            const isToday = i === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            calendarGrid.appendChild(createDayElement(i, year, month, false, isToday));
        }

        const totalSlots = calendarGrid.children.length > 35 ? 42 : 35;
        const remainingSlots = totalSlots - calendarGrid.children.length;
        for (let i = 1; i <= remainingSlots; i++) {
            calendarGrid.appendChild(createDayElement(i, year, month + 1, true));
        }
    }

    function createDayElement(dayNumber, year, month, isPadding, isToday = false) {
        const d = new Date(year, month, dayNumber);
        const dateStr = toLocalDateString(d);
        const dayAppointments = appointmentsByDate.get(dateStr) || [];

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `flex min-h-14 flex-col items-stretch gap-1 rounded-control border p-1 text-left transition-colors hover:bg-canvas aria-pressed:ring-2 aria-pressed:ring-ink xl:min-h-24 xl:p-2 ${
            isToday ? 'border-primary bg-primary-soft' : isPadding ? 'border-field bg-canvas' : 'border-field bg-surface'
        }`;
        button.setAttribute('data-pressable', '');
        button.setAttribute('data-agenda-date', dateStr);
        button.setAttribute('aria-pressed', dateStr === selectedDate ? 'true' : 'false');
        button.setAttribute('aria-label', `${longDate(d)}${isToday ? ', hoy' : ''}, ${countLabel(dayAppointments.length)}`);

        if (isToday) {
            button.setAttribute('aria-current', 'date');
        }

        button.addEventListener('click', () => {
            openDayDetails(dateStr, button);
        });

        const number = document.createElement('span');
        number.className = `text-sm font-semibold ${isPadding ? 'text-muted' : 'text-ink'}`;
        number.textContent = dayNumber;
        button.appendChild(number);

        if (dayAppointments.length > 0) {
            const count = document.createElement('span');
            count.className = 'inline-flex min-h-6 items-center justify-center rounded-full bg-info-soft px-1 text-min font-bold text-info xl:hidden';
            count.textContent = dayAppointments.length;
            button.appendChild(count);

            dayAppointments.slice(0, 3).forEach(appt => {
                const done = normalizeAppointmentStatus(appt.status) === 'completada';
                const label = document.createElement('span');
                label.className = `hidden rounded-control border-l-2 px-1.5 py-1 text-min font-semibold text-ink wrap-anywhere xl:block ${
                    done ? 'border-success bg-success-soft' : 'border-info bg-info-soft'
                }`;
                label.textContent = `${shortTime(appt.time)} ${appt.patient_name || 'Paciente'}`;
                button.appendChild(label);
            });

            if (dayAppointments.length > 3) {
                const more = document.createElement('span');
                more.className = 'hidden text-min font-semibold text-muted xl:block';
                more.textContent = `+ ${dayAppointments.length - 3} más`;
                button.appendChild(more);
            }
        }

        return button;
    }

    function markSelectedDay() {
        if (!calendarGrid) return;

        calendarGrid.querySelectorAll('[data-agenda-date]').forEach(button => {
            button.setAttribute('aria-pressed', button.getAttribute('data-agenda-date') === selectedDate ? 'true' : 'false');
        });
    }

    function renderSelectedDay() {
        if (!selectedDate) return;

        const title = `Citas del ${longDate(fromLocalDateString(selectedDate))}`;
        const dayAppointments = appointmentsByDate.get(selectedDate) || [];
        const emptyText = 'No hay citas para este día.';

        if (dayTitle) dayTitle.textContent = title;
        renderAppointmentList(dayList, dayAppointments, emptyText);

        if (dayDetailsModal && isDialogOpen(dayDetailsModal)) {
            if (modalDateDisplay) modalDateDisplay.textContent = capitalize(longDate(fromLocalDateString(selectedDate)));
            renderAppointmentList(modalAppointmentsList, dayAppointments, emptyText);
        }
    }

    function openDayDetails(dateStr, opener) {
        selectedDate = dateStr;
        markSelectedDay();
        renderSelectedDay();

        if (!dayDetailsModal || !dialogFromHere.matches) {
            return;
        }

        if (modalDateDisplay) modalDateDisplay.textContent = capitalize(longDate(fromLocalDateString(dateStr)));
        renderAppointmentList(modalAppointmentsList, appointmentsByDate.get(dateStr) || [], 'No hay citas para este día.');
        openDialog(dayDetailsModal, opener);
    }

    function setActiveStatusButton(value) {
        if (!statusFilter) {
            return;
        }

        statusFilter.querySelectorAll('[data-status-value]').forEach(function (button) {
            var isActive = String(button.getAttribute('data-status-value') || '') === String(value || '');
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function handleStatusFilter(event) {
        var button = event.target.closest('[data-status-value]');

        if (!button || !statusFilter || !statusFilter.contains(button)) {
            return;
        }

        selectedStatus = String(button.getAttribute('data-status-value') || '');
        setActiveStatusButton(selectedStatus);
        fetchAppointments();
    }

    // Event Listeners
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('click', handleStatusFilter);
        setActiveStatusButton(selectedStatus);
    }

    window.agendaPage = {
        reload: fetchAppointments,
        getAppointments: () => appointments.slice(),
    };

    // Initialize
    renderCalendar();
    fetchAppointments();

})();
