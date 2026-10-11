<x-ui.dialog id="complete-appointment-modal" title="Completar cita" size="lg" data-complete-appointment-modal>
    <p class="text-sm font-semibold text-ink wrap-anywhere" data-complete-appointment-summary>Paciente • Tratamiento</p>
    <p class="mt-1 text-sm text-muted" data-complete-appointment-step-indicator>Paso 1 de 2 · Datos clínicos</p>

    <form id="complete-appointment-form" data-complete-appointment-form class="mt-4" novalidate>
        <div data-complete-appointment-step="1" class="space-y-4">
            <div>
                <label for="complete-appointment-reason" class="mb-1.5 block text-sm font-semibold text-ink">
                    Motivo de la consulta
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <textarea id="complete-appointment-reason" rows="2" data-complete-appointment-reason class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
            </div>

            <div>
                <label for="complete-appointment-symptoms" class="mb-1.5 block text-sm font-semibold text-ink">
                    Síntomas (opcional)
                </label>
                <textarea id="complete-appointment-symptoms" rows="2" data-complete-appointment-symptoms class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" aria-describedby="complete-appointment-symptoms-hint"></textarea>
                <p id="complete-appointment-symptoms-hint" class="mt-1 text-min text-muted">Sepáralos con comas.</p>
            </div>

            <div>
                <label for="complete-appointment-diagnosis" class="mb-1.5 block text-sm font-semibold text-ink">
                    Diagnóstico
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <textarea id="complete-appointment-diagnosis" rows="2" data-complete-appointment-diagnosis class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
            </div>

            <div>
                <label for="complete-appointment-procedure" class="mb-1.5 block text-sm font-semibold text-ink">
                    Procedimiento realizado
                    <span class="text-danger" title="Obligatorio">*</span>
                </label>
                <textarea id="complete-appointment-procedure" rows="2" data-complete-appointment-procedure class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink" required></textarea>
            </div>

            <div>
                <label for="complete-appointment-observations" class="mb-1.5 block text-sm font-semibold text-ink">
                    Observaciones (opcional)
                </label>
                <textarea id="complete-appointment-observations" rows="2" data-complete-appointment-observations class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink"></textarea>
            </div>

            <div>
                <label for="complete-appointment-recommendations" class="mb-1.5 block text-sm font-semibold text-ink">
                    Recomendaciones (opcional)
                </label>
                <textarea id="complete-appointment-recommendations" rows="2" data-complete-appointment-recommendations class="block w-full rounded-control border border-field bg-surface px-3 py-2.5 text-control text-ink"></textarea>
            </div>
        </div>

        <div data-complete-appointment-step="2" class="hidden space-y-4">
            <x-ui.section-title title="Prescripciones">
                <x-slot:action>
                    <x-ui.button data-complete-appointment-add-prescription>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Agregar prescripción
                    </x-ui.button>
                </x-slot:action>
            </x-ui.section-title>

            <p class="text-sm text-muted" data-complete-appointment-prescriptions-empty>
                Sin prescripciones. Puedes completar la cita sin agregar ninguna.
            </p>

            <div class="space-y-3" data-complete-appointment-prescriptions-list></div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button data-complete-appointment-cancel>Cancelar</x-ui.button>
        <x-ui.button hidden data-complete-appointment-back>Atrás</x-ui.button>
        <x-ui.button variant="primary" data-complete-appointment-next>Siguiente</x-ui.button>
        <x-ui.button variant="primary" hidden data-complete-appointment-submit>Completar cita</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>

{{-- Pregunta de confirmación: descartar lo capturado o completar la cita. --}}
<x-ui.dialog id="complete-appointment-confirm" title="Confirmar" size="sm" data-complete-appointment-confirm>
    <p class="text-sm font-semibold text-ink" data-complete-appointment-confirm-title>¿Estás seguro?</p>
    <p class="mt-2 text-sm leading-6 text-ink" data-complete-appointment-confirm-message></p>

    <x-slot:footer>
        <x-ui.button data-complete-appointment-confirm-cancel>
            <span data-complete-appointment-confirm-cancel-text>Seguir editando</span>
        </x-ui.button>
        <x-ui.button variant="primary" data-complete-appointment-confirm-accept>
            <span data-complete-appointment-confirm-accept-text>Confirmar</span>
        </x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
