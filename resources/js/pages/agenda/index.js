(function () {
    if (window.__agendaIndexInit) {
        return;
    }

    window.__agendaIndexInit = true;

    // State
    let currentDate = new Date();
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
    
    // Selectors
    const calendarGrid = document.getElementById('calendar-grid');
    const monthDisplay = document.getElementById('current-month-display');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const todayAppointmentsContainer = document.getElementById('today-appointments-container');
    const statusFilter = document.querySelector('[data-agenda-status-filter]');

    // Stats Selectors
    const statToday = document.querySelector('[data-stat-today]');
    const statPending = document.querySelector('[data-stat-pending]');
    const statConfirmed = document.querySelector('[data-stat-confirmed]');
    const statCompleted = document.querySelector('[data-stat-completed]');

    // Modal Selectors
    const dayDetailsModal = document.getElementById('day-details-modal');
    const modalDateDisplay = document.getElementById('modal-date-display');
    const modalAppointmentsList = document.getElementById('modal-appointments-list');
    const closeModalBtns = document.querySelectorAll('[data-close-modal]');

    const isAdminUser = document.querySelector('[data-agenda-is-admin]')?.getAttribute('data-agenda-is-admin') === 'true';

    function toLocalDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
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

            if (window.agendaAppointmentModal && typeof window.agendaAppointmentModal.refreshAvailability === 'function') {
                window.agendaAppointmentModal.refreshAvailability();
            }
        } catch (error) {
            console.error('Error fetching appointments:', error);
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
        if (!appointments.length) return;

        if (statToday) statToday.textContent = appointmentStats.today;
        if (statPending) statPending.textContent = appointmentStats.pending;
        if (statConfirmed) statConfirmed.textContent = appointmentStats.confirmed;
        if (statCompleted) statCompleted.textContent = appointmentStats.completed;
    }

    function renderTodayAppointments() {
        if (!todayAppointmentsContainer) return;
        
        todayAppointmentsContainer.innerHTML = '';

        if (appointmentsToday.length === 0) {
            todayAppointmentsContainer.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">No hay citas para hoy</p>';
        } else {
            appointmentsToday.forEach(appt => {
                todayAppointmentsContainer.appendChild(createAppointmentCard(appt));
            });
        }
    }

    function createAppointmentCard(appt) {
        const article = document.createElement('article');
        article.className = 'relative box-border flex gap-4 rounded-2xl bg-white p-4 shadow-sm transition-transform hover:scale-[1.02]';
        
        const timeParts = appt.time.split(':');
        const hour = parseInt(timeParts[0]);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedTime = `${hour % 12 || 12}:${timeParts[1]}`;

        const normalizedStatus = normalizeAppointmentStatus(appt.status);
        const statusColors = {
            'asignada': 'bg-amber-50 text-amber-700',
            'completada': 'bg-emerald-50 text-emerald-700',
            'cancelada': 'bg-red-50 text-red-700',
            'reprogramada': 'bg-blue-50 text-blue-700'
        };
        const statusLabel = {
            'asignada': 'Asignada',
            'completada': 'Completada',
            'cancelada': 'Cancelada',
            'reprogramada': 'Reprogramada'
        };

        const isActionable = normalizedStatus === 'asignada' || normalizedStatus === 'reprogramada';
        const eyeIcon = `
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        `;
        const pencilIcon = `
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L7.5 21H3v-4.5L15.232 5.232z" />
            </svg>
        `;

        let actionsHtml;
        if (isActionable && isAdminUser) {
            actionsHtml = `
                <div class="ml-auto flex shrink-0 flex-col items-end gap-2">
                    <button type="button" class="rounded-xl bg-[#E91E63] px-3 py-2 text-[11px] font-bold text-white transition hover:bg-[#d61b5b]" data-complete-appointment-trigger data-appointment-id="${appt.id}">
                        Completar
                    </button>
                    <button type="button" class="rounded-full border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:border-[#B5114A] hover:text-[#B5114A]" data-view-appointment-trigger data-appointment-id="${appt.id}" aria-label="Ver detalles">
                        ${eyeIcon}
                    </button>
                </div>
            `;
        } else if (isActionable) {
            actionsHtml = `
                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <button type="button" class="rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-[#B5114A] hover:text-[#B5114A]" data-view-appointment-trigger data-appointment-id="${appt.id}" aria-label="Ver detalles">
                        ${eyeIcon}
                    </button>
                    <button type="button" class="rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-[#B5114A] hover:text-[#B5114A]" data-edit-appointment-trigger data-appointment-id="${appt.id}" aria-label="Editar cita">
                        ${pencilIcon}
                    </button>
                </div>
            `;
        } else {
            actionsHtml = `
                <button type="button" class="ml-auto shrink-0 rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-[#B5114A] hover:text-[#B5114A]" data-view-appointment-trigger data-appointment-id="${appt.id}" aria-label="Ver detalles">
                    ${eyeIcon}
                </button>
            `;
        }

        article.innerHTML = `
            <div class="flex flex-col items-center justify-center border-r border-slate-100 pr-4 text-center">
                <span class="text-sm font-bold text-slate-900">${formattedTime}</span>
                <span class="text-[10px] font-bold uppercase text-slate-400">${ampm}</span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-slate-900">${appt.patient_name || 'Paciente'}</h4>
                <p class="text-xs text-slate-500">${appt.treatment_name || 'Consulta'}</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="rounded-full ${statusColors[normalizedStatus] || 'bg-slate-50 text-slate-500'} px-2 py-0.5 text-[10px] font-bold">
                        ${statusLabel[normalizedStatus] || appt.status}
                    </span>
                </div>
            </div>
            ${actionsHtml}
        `;

        const completeButton = article.querySelector('[data-complete-appointment-trigger]');
        if (completeButton) {
            completeButton.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.agendaCompleteAppointmentModal && typeof window.agendaCompleteAppointmentModal.open === 'function') {
                    window.agendaCompleteAppointmentModal.open(appt.id);
                }
            });
        }

        const viewButton = article.querySelector('[data-view-appointment-trigger]');
        if (viewButton) {
            viewButton.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.agendaViewAppointmentModal && typeof window.agendaViewAppointmentModal.open === 'function') {
                    window.agendaViewAppointmentModal.open(appt.id);
                }
            });
        }

        const editButton = article.querySelector('[data-edit-appointment-trigger]');
        if (editButton) {
            editButton.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.agendaAppointmentModal && typeof window.agendaAppointmentModal.openEdit === 'function') {
                    window.agendaAppointmentModal.openEdit(appt.id);
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
            const dayDiv = createDayElement(daysInPrevMonth - i + 1, year, month - 1, true);
            calendarGrid.appendChild(dayDiv);
        }

        const today = new Date();
        for (let i = 1; i <= daysInMonth; i++) {
            const isToday = i === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const dayDiv = createDayElement(i, year, month, false, isToday);
            calendarGrid.appendChild(dayDiv);
        }

        const totalSlots = calendarGrid.children.length > 35 ? 42 : 35;
        const remainingSlots = totalSlots - calendarGrid.children.length;
        for (let i = 1; i <= remainingSlots; i++) {
            const dayDiv = createDayElement(i, year, month + 1, true);
            calendarGrid.appendChild(dayDiv);
        }
    }

    function createDayElement(dayNumber, year, month, isPadding, isToday = false) {
        const d = new Date(year, month, dayNumber);
        const dateStr = toLocalDateString(d);
        
        const dayAppointments = appointmentsByDate.get(dateStr) || [];

        const div = document.createElement('div');
        div.className = `min-h-[100px] rounded-2xl border ${
            isToday 
            ? 'border-[#F5C2D6] bg-[#FFF7FA]' 
            : isPadding ? 'border-slate-50 bg-slate-50/30' : 'border-slate-100 bg-slate-50/50'
        } p-2 transition-all hover:shadow-sm cursor-pointer`;

        div.addEventListener('click', () => {
            openDayDetails(d, dayAppointments);
        });

        const span = document.createElement('span');
        span.className = `text-xs font-bold ${
            isToday ? 'text-[#B5114A]' : isPadding ? 'text-slate-300' : 'text-slate-400'
        }`;
        span.textContent = dayNumber;

        div.appendChild(span);

        if (dayAppointments.length > 0) {
            const appointmentsDiv = document.createElement('div');
            appointmentsDiv.className = 'mt-2 space-y-1';
            
            dayAppointments.slice(0, 3).forEach(appt => {
                const normalizedStatus = normalizeAppointmentStatus(appt.status);
                const colorClass = normalizedStatus === 'completada' ? 'bg-[#FDF1F6] text-[#B5114A] border-[#B5114A]' : 'bg-sky-50 text-sky-700 border-sky-600';
                
                const apptEl = document.createElement('div');
                apptEl.className = `rounded-lg ${colorClass} p-1.5 text-[10px] font-bold border-l-2 truncate`;
                apptEl.textContent = `${appt.time.substring(0, 5)} - ${appt.patient_name || 'Paciente'}`;
                appointmentsDiv.appendChild(apptEl);
            });

            if (dayAppointments.length > 3) {
                const moreEl = document.createElement('div');
                moreEl.className = 'text-[9px] font-bold text-slate-400 text-center';
                moreEl.textContent = `+ ${dayAppointments.length - 3} más`;
                appointmentsDiv.appendChild(moreEl);
            }

            div.appendChild(appointmentsDiv);
        }

        return div;
    }

    function openDayDetails(date, dayAppointments) {
        if (!dayDetailsModal) return;

        modalDateDisplay.textContent = `Citas del ${date.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`;
        modalAppointmentsList.innerHTML = '';

        if (dayAppointments.length === 0) {
            modalAppointmentsList.innerHTML = '<p class="text-center text-slate-500 py-8">No hay citas agendadas para este día.</p>';
        } else {
            dayAppointments.forEach(appt => {
                modalAppointmentsList.appendChild(createAppointmentCard(appt));
            });
        }

        dayDetailsModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (dayDetailsModal) {
            dayDetailsModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function setActiveStatusButton(value) {
        if (!statusFilter) {
            return;
        }

        statusFilter.querySelectorAll('[data-status-value]').forEach(function (button) {
            var isActive = String(button.getAttribute('data-status-value') || '') === String(value || '');
            button.classList.toggle('bg-[#FDF1F6]', isActive);
            button.classList.toggle('text-[#B5114A]', isActive);
            button.classList.toggle('text-slate-600', !isActive);
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

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

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
