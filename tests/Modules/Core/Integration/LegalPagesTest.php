<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * The public privacy notice and terms and conditions: their routes, what they must say, where
 * they are linked from, and that the data of the controller comes from config/legal.php.
 */

const LEGAL_PAGES = [
    'aviso-privacidad' => ['/aviso-de-privacidad', 'Aviso de Privacidad', 'privacy'],
    'terminos' => ['/terminos-y-condiciones', 'Términos y Condiciones', 'terms'],
];

function legalPage(CoreIntegrationTestCase $test, string $path): DOMXPath
{
    $html = $test->get($path)->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

it('serves the legal page with its title, version and date', function (string $path, string $title, string $configKey) {
    config([
        "legal.{$configKey}.version" => '2.3',
        "legal.{$configKey}.updated_at" => '2026-12-01',
    ]);

    $page = legalPage($this, $path);

    expect(trim($page->query('//h1')->item(0)->textContent))->toBe($title)
        ->and($page->query('//title')->item(0)->textContent)->toBe("{$title} - Dentissa")
        ->and($page->query('//time[@datetime="2026-12-01"]')->length)->toBe(1)
        ->and($page->query('//article')->item(0)->textContent)->toContain('Versión 2.3')
        ->and($page->query('//article')->item(0)->textContent)->toContain('1 de diciembre de 2026');
})->with(LEGAL_PAGES);

it('describes the legal page for search engines and lets them index it', function (string $path) {
    $page = legalPage($this, $path);
    $description = $page->query('//meta[@name="description"]');

    expect($description->length)->toBe(1)
        ->and(mb_strlen($description->item(0)->getAttribute('content')))->toBeGreaterThanOrEqual(70)
        ->and(mb_strlen($description->item(0)->getAttribute('content')))->toBeLessThanOrEqual(160)
        ->and($page->query('//meta[@name="robots"]')->length)->toBe(0);
})->with(LEGAL_PAGES);

it('links every entry of the index to a section of the page', function (string $path) {
    $page = legalPage($this, $path);
    $entries = $page->query('//nav[@aria-labelledby="legal-index-title"]//a');

    expect($entries->length)->toBeGreaterThan(0);

    foreach ($entries as $entry) {
        $id = ltrim($entry->getAttribute('href'), '#');

        expect($page->query('//section[@id="'.$id.'"]/h2')->length)->toBe(1, "Sin sección para #{$id}");
    }
})->with(LEGAL_PAGES);

it('shows the data of the controller from config/legal.php', function (string $path) {
    config([
        'legal.controller.name' => 'Clínica Dental de Prueba, S.A. de C.V.',
        'legal.controller.privacy_email' => 'privacidad@prueba.example',
    ]);

    $text = legalPage($this, $path)->query('//article')->item(0)->textContent;

    expect($text)->toContain('Clínica Dental de Prueba, S.A. de C.V.')
        ->and($text)->toContain('privacidad@prueba.example');
})->with(LEGAL_PAGES);

it('covers what the privacy notice must tell a patient', function () {
    $page = legalPage($this, '/aviso-de-privacidad');

    foreach (['responsable', 'datos', 'finalidades', 'negativa', 'proveedores', 'derechos', 'revocacion', 'cookies', 'cambios'] as $section) {
        expect($page->query('//section[@id="'.$section.'"]')->length)->toBe(1, "Falta la sección #{$section}");
    }

    $text = $page->query('//article')->item(0)->textContent;

    expect($text)->toContain('datos personales sensibles');

    foreach (['Twilio', 'Brevo', 'Cloudflare', 'DigitalOcean'] as $provider) {
        expect($text)->toContain($provider);
    }
});

it('links each legal page to the other one', function () {
    expect(legalPage($this, '/aviso-de-privacidad')->query('//article//a[@href="'.route('terminos').'"]')->length)->toBe(1)
        ->and(legalPage($this, '/terminos-y-condiciones')->query('//article//a[@href="'.route('aviso-privacidad').'"]')->length)->toBe(1);
});

it('links the footer of the public site to both legal pages', function (string $path) {
    $footer = '//footer';
    $page = legalPage($this, $path);

    expect($page->query($footer.'//a[@href="'.route('aviso-privacidad').'" and normalize-space()="Aviso de Privacidad"]')->length)->toBe(1)
        ->and($page->query($footer.'//a[@href="'.route('terminos').'" and normalize-space()="Términos y Condiciones"]')->length)->toBe(1)
        ->and($page->query($footer.'//a[@href="#"]')->length)->toBe(0);
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto', '/aviso-de-privacidad', '/terminos-y-condiciones']);

it('tells a patient who signs up that they accept both legal pages', function () {
    $page = legalPage($this, '/register');
    $legend = $page->query('//form//*[@id="register-legal"]/p');

    expect($legend->length)->toBe(1)
        ->and(trim($legend->item(0)->textContent))->toBe('Al registrarte aceptas el Aviso de Privacidad y los Términos y Condiciones.')
        ->and($page->query('//*[@id="register-legal"]//a[@href="'.route('aviso-privacidad').'"]')->length)->toBe(1)
        ->and($page->query('//*[@id="register-legal"]//a[@href="'.route('terminos').'"]')->length)->toBe(1);
});
