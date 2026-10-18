<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 018, "Qué ve cada rol": the agenda screen opens to the three staff roles and each one
 * only gets the controls and dialogs it may use. Hiding them is presentation only; the API
 * refuses the rest (AppointmentsAccessControlTest, DoctorTrackingTest).
 */
beforeEach(function () {
    $this->withoutVite();
});

function actAsAgendaRole(object $test, string $role): void
{
    (function () use ($role): void {
        $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);
    })->call($test);
}

it('opens the agenda to every staff role', function (string $role) {
    actAsAgendaRole($this, $role);

    $this->get('/agenda')->assertOk()->assertSee('Agenda de citas');
})->with(['Administrador', 'Asistente', 'Doctor']);

it('refuses the agenda to a patient and to inactive staff with the "Sin permiso" page', function (string $actor) {
    $actor === 'paciente' ? $this->actingAsPatient() : $this->actingAsInactiveAdmin();

    $this->get('/agenda')->assertForbidden()->assertViewIs('errors::403');
})->with(['paciente', 'staff inactivo']);

it('gives the administrator and the assistant booking, rescheduling and cancelling', function (string $role) {
    actAsAgendaRole($this, $role);

    $this->get('/agenda')->assertOk()
        ->assertSee('Agendar nueva cita')
        ->assertSee('data-create-appointment-open', false)
        ->assertSee('data-create-appointment-modal', false)
        ->assertSee('data-edit-appointment-modal', false)
        ->assertSee('data-cancel-appointment-confirm', false)
        ->assertSee('Consulta y organiza las citas de la clínica.');
})->with(['Administrador', 'Asistente']);

it('shows the patient and doctor selectors of the booking dialog to the administrator and the assistant', function (string $role) {
    actAsAgendaRole($this, $role);

    $this->get('/agenda')->assertOk()
        ->assertSee('data-create-appointment-with-selectors="true"', false)
        ->assertSee('data-create-appointment-patient-select', false)
        ->assertSee('data-create-appointment-doctor-select', false)
        ->assertDontSee('data-create-appointment-is-admin', false);
})->with(['Administrador', 'Asistente'])->skip('018: pendiente de T040');

it('gives completing to the administrator and the doctor, not to the assistant', function (string $role, bool $canComplete) {
    actAsAgendaRole($this, $role);

    $response = $this->get('/agenda')->assertOk()
        ->assertSee('data-agenda-can-complete="'.($canComplete ? 'true' : 'false').'"', false);

    $canComplete
        ? $response->assertSee('data-complete-appointment-modal', false)
        : $response->assertDontSee('data-complete-appointment-modal', false);
})->with([
    'administrador' => ['Administrador', true],
    'asistente' => ['Asistente', false],
    'doctor' => ['Doctor', true],
]);

it('shows the doctor their agenda without booking, rescheduling or cancelling', function () {
    actAsAgendaRole($this, 'Doctor');

    $this->get('/agenda')->assertOk()
        ->assertSee('Tus citas asignadas.')
        ->assertSee('data-agenda-empty-today="No tienes citas para hoy."', false)
        ->assertDontSee('Agendar nueva cita')
        ->assertDontSee('data-create-appointment-open', false)
        ->assertDontSee('data-create-appointment-modal', false)
        ->assertDontSee('data-edit-appointment-modal', false)
        ->assertDontSee('data-cancel-appointment-confirm', false);
});

it('keeps the default empty text of today for the administrator and the assistant', function (string $role) {
    actAsAgendaRole($this, $role);

    $this->get('/agenda')->assertOk()
        ->assertSee('data-agenda-empty-today="No hay citas para hoy."', false)
        ->assertDontSee('Tus citas asignadas.');
})->with(['Administrador', 'Asistente']);
