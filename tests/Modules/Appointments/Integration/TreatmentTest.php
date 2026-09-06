<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

// -----------------------------------------------------------------
// Crear tratamiento
// -----------------------------------------------------------------

it('admin can create a treatment', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload());

    $response->assertStatus(201);
    $this->assertDatabaseCount('treatments', 1);
});

it('non-admin staff gets 403 (blocked by only.admin middleware before reaching the use case)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload());

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only administrators can access this resource.']);
});

it('a patient gets 403 with "Only users can access this resource."', function () {
    $this->actingAsPatient();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload());

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only users can access this resource.']);
});

it('rejects an empty treatment name', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload(['name' => '   ']));

    $response->assertStatus(400);
});

it('rejects an empty treatment description', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload(['description' => '']));

    $response->assertStatus(400);
});

it('rejects a treatment duration outside [0, 240]', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->treatmentsAdminUrl(), $this->validCreateTreatmentPayload(['time' => '241']));

    $response->assertStatus(400);
});

// -----------------------------------------------------------------
// Actualizar tratamiento
// -----------------------------------------------------------------

it('admin can update a treatment', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment();

    $response = $this->putJson($this->treatmentAdminUrl($treatment->id), ['name' => 'Nuevo nombre']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('treatments', ['id' => $treatment->id, 'name' => 'Nuevo nombre']);
});

it('non-admin cannot update a treatment', function () {
    $this->actingAsNonAdminUser();
    $treatment = $this->createTreatment();

    $response = $this->putJson($this->treatmentAdminUrl($treatment->id), ['name' => 'Nuevo nombre']);

    $response->assertStatus(403);
});

// -----------------------------------------------------------------
// Eliminar tratamiento
// -----------------------------------------------------------------

it('admin can delete a treatment', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment();

    $response = $this->deleteJson($this->treatmentAdminUrl($treatment->id));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('treatments', ['id' => $treatment->id]);
});

it('non-admin cannot delete a treatment', function () {
    $this->actingAsNonAdminUser();
    $treatment = $this->createTreatment();

    $response = $this->deleteJson($this->treatmentAdminUrl($treatment->id));

    $response->assertStatus(403);
});

it('returns 404 when deleting a non-existent treatment', function () {
    $this->actingAsAdmin();

    $response = $this->deleteJson($this->treatmentAdminUrl(999999));

    $response->assertStatus(404);
});

// -----------------------------------------------------------------
// Consultar tratamiento(s) - admin listado/detalle
// -----------------------------------------------------------------

it('admin can list treatments via the admin endpoint', function () {
    $this->actingAsAdmin();
    $this->createTreatment(['name' => 'Limpieza']);
    $this->createTreatment(['name' => 'Extraccion']);

    $response = $this->getJson($this->treatmentsAdminUrl());

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('non-admin cannot list treatments via the admin endpoint', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->treatmentsAdminUrl());

    $response->assertStatus(403);
});

it('admin can get a treatment by id', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment();

    $response = $this->getJson($this->treatmentAdminUrl($treatment->id));

    $response->assertStatus(200);
});
