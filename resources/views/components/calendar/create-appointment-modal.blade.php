@props([
    'modalId' => 'create-appointment-modal',
    'title' => 'Nueva cita',
    'saveText' => 'Guardar cita',
    'cancelText' => 'Cancelar',
    'isAdmin' => false,
    'currentPatientId' => '',
    'currentUserId' => '',
])

<div
    id="{{ $modalId }}"
    data-create-appointment-modal
    data-create-appointment-is-admin="{{ $isAdmin ? 'true' : 'false' }}"
    data-create-appointment-current-patient-id="{{ $currentPatientId }}"
    data-create-appointment-current-user-id="{{ $currentUserId }}"
    class="fixed inset-0 z-100 hidden overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-create-appointment-cancel></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-2xl transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#B5114A]">Agenda</p>
                            <h3 class="mt-1 text-xl font-extrabold text-slate-900">{{ $title }}</h3>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-500" data-create-appointment-cancel>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form data-create-appointment-form class="mt-6 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            @if ($isAdmin)
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-slate-700">Paciente</label>
                                    <select
                                        name="patient_id"
                                        data-create-appointment-patient-select
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                        required
                                    >
                                        <option value="">Cargando pacientes...</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-bold text-slate-700">Doctor</label>
                                    <select
                                        name="user_id"
                                        data-create-appointment-doctor-select
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                        required
                                    >
                                        <option value="">Cargando doctores...</option>
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="patient_id" data-create-appointment-patient-hidden value="{{ $currentPatientId }}">
                                <input type="hidden" name="user_id" data-create-appointment-user-hidden value="{{ $currentUserId }}">
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Tratamiento</label>
                                <select
                                    name="treatment_id"
                                    data-create-appointment-treatment-select
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                                    <option value="">Cargando tratamientos...</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Fecha</label>
                                <input
                                    type="date"
                                    name="date"
                                    data-create-appointment-date
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Hora</label>
                                <select
                                    name="time"
                                    data-create-appointment-time-select
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 transition-all focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10"
                                    required
                                >
                                    <option value="">Selecciona un tratamiento primero</option>
                                </select>
                            </div>
                        </div>

                        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800">
                            Las horas disponibles se ajustan según las citas del día y la duración del tratamiento seleccionado.
                        </p>

                        <p data-create-appointment-error class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"></p>

                        <div class="flex items-center justify-end gap-3 pt-4">
                            <button
                                type="button"
                                data-create-appointment-cancel
                                class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                {{ $cancelText }}
                            </button>
                            <button
                                type="submit"
                                data-create-appointment-submit
                                disabled
                                class="cursor-pointer rounded-2xl bg-[#E91E63] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] disabled:cursor-not-allowed disabled:opacity-60 active:scale-95"
                            >
                                {{ $saveText }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>