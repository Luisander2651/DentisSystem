<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA30: the texts that used to be the same for everyone now name the role of who
 * reads them ("Qué ve cada rol" of the plan) - the badge of the page header, and the role
 * shown in the menu and at the start of the panel, in Spanish.
 *
 * The markup carries three hooks for it: `data-page-badge` on the badge of x-ui.page-hero,
 * `data-role-label="menu"` on the role of the side menu and `data-role-label="inicio"` on the
 * role of the start of the panel. A case waits for the task of ROLE_COPY_PENDING (A61).
 */

const ROLE_COPY = [
    'administrador' => ['Administrador', 'Panel de administración', 'Administrador'],
    'asistente' => ['Asistente', 'Panel de asistente', 'Asistente'],
    'doctor' => ['Doctor', 'Panel clínico', 'Doctor'],
    'paciente' => ['paciente', 'Mi cuenta', 'Paciente'],
];

/** check => task that unblocks its cases. */
const ROLE_COPY_PENDING = [
    'badge' => null,
    'menu' => null,
    'inicio' => null,
];

/**
 * The texts of the elements a screen marks with an attribute, for the given actor.
 *
 * @return list<string>
 */
function roleCopyTexts(CoreIntegrationTestCase $test, string $check, string $actor, string $path, string $query): array
{
    if (ROLE_COPY_PENDING[$check] !== null) {
        $test->markTestSkipped('016: pendiente de '.ROLE_COPY_PENDING[$check]);
    }

    $html = (function () use ($actor, $path): string {
        match ($actor) {
            'paciente' => $this->actingAsPatient(),
            'Administrador' => $this->actingAsAdmin(),
            default => $this->actingAsNonAdminUser($actor),
        };

        return $this->get($path)->assertOk()->getContent();
    })->call($test);

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $texts = [];

    foreach ((new DOMXPath($document))->query($query) as $node) {
        $texts[] = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
    }

    return $texts;
}

it('names the role in the badge of the page header', function (string $role, string $path) {
    [$actor, $badge] = ROLE_COPY[$role];

    $texts = roleCopyTexts($this, 'badge', $actor, $path, '//*[@data-page-badge]');

    expect($texts)->toBe([$badge]);
})->with([
    'administrador en su inicio' => ['administrador', '/dashboard'],
    'asistente en su inicio' => ['asistente', '/dashboard'],
    'doctor en su inicio' => ['doctor', '/dashboard'],
    'paciente en su inicio' => ['paciente', '/dashboard'],
    'administrador en pacientes' => ['administrador', '/pacientes'],
    'administrador en expedientes' => ['administrador', '/expedientes-clinicos'],
    'asistente en expedientes' => ['asistente', '/expedientes-clinicos'],
    'doctor en expedientes' => ['doctor', '/expedientes-clinicos'],
]);

it('shows the role in Spanish in the side menu', function (string $role) {
    [$actor, , $label] = ROLE_COPY[$role];

    $texts = roleCopyTexts($this, 'menu', $actor, '/dashboard', '//*[@data-role-label="menu"]');

    expect($texts)->not->toBeEmpty()
        ->and(array_unique($texts))->toBe([$label]);
})->with(array_keys(ROLE_COPY));

it('shows the role in Spanish at the start of the panel', function (string $role) {
    [$actor, , $label] = ROLE_COPY[$role];

    $texts = roleCopyTexts($this, 'inicio', $actor, '/dashboard', '//*[@data-role-label="inicio"]');

    expect($texts)->toBe([$label]);
})->with(array_keys(ROLE_COPY));

it('never shows the internal name of a role', function (string $role) {
    [$actor] = ROLE_COPY[$role];

    $texts = roleCopyTexts($this, 'inicio', $actor, '/dashboard', '//body//text()[not(ancestor::script) and not(ancestor::style)]');

    expect(implode(' ', $texts))->not->toMatch('/\b(patient|admin|para administradores)\b/i');
})->with(array_keys(ROLE_COPY));
