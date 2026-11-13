<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 026, CA1 and CA7 (TM4; A10, A19, A40 of /analyze 026): the layout of the public site — its
 * bar, footer and floating WhatsApp button — on a test page that extends it. The language, the
 * clinic's own logo and tab icons, the contact details of config/site.php, the legal links and
 * the safety of the links that open another tab.
 */

const SITE_TEST_PATH = '/prueba-del-sitio';

beforeEach(function () {
    $this->withoutVite();

    Route::middleware('web')->get(SITE_TEST_PATH, fn (): string => Blade::render(<<<'BLADE'
        @extends('layouts.site')
        @section('title', 'Página de prueba - Dentissa')
        @section('description', 'Descripción de la página de prueba del sitio público de Dentissa, solo para los tests del layout.')
        @section('content')
            <h1>Página de prueba</h1>
        @endsection
        BLADE))->name('prueba-del-sitio');
});

function sitePage(ContentManagementIntegrationTestCase $test, string $path = SITE_TEST_PATH): DOMXPath
{
    $html = $test->get($path)->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

/**
 * @return list<string>
 */
function siteTexts(DOMXPath $page, string $query): array
{
    return array_map(fn (DOMNode $node): string => trim(preg_replace('/\s+/', ' ', $node->textContent)), iterator_to_array($page->query($query)));
}

/**
 * @return list<string>
 */
function siteHrefs(DOMXPath $page, string $query = '//a'): array
{
    return array_map(fn (DOMElement $link): string => $link->getAttribute('href'), iterator_to_array($page->query($query)));
}

it('declares the page in Spanish with its own title and a single description', function () {
    $page = sitePage($this);

    expect($page->query('/html')->item(0)->getAttribute('lang'))->toBe('es')
        ->and(siteTexts($page, '//title'))->toBe(['Página de prueba - Dentissa'])
        ->and($page->query('//meta[@name="description"]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1);
});

it('shows the clinic\'s own logo and tab icons, not those of the proposal', function () {
    $page = sitePage($this);
    $logos = $page->query('//img[contains(@src, "/images/brand/icon-64.png")]');

    expect($logos->length)->toBe(2);

    foreach ($logos as $logo) {
        expect($logo->getAttribute('alt'))->toBe('Logo de Dentissa')
            ->and($logo->getAttribute('data-origen'))->toBe('propia');
    }

    expect($page->query('//link[@rel="icon"]')->length)->toBeGreaterThan(0)
        ->and($page->query('//link[@rel="apple-touch-icon"]')->length)->toBe(1)
        ->and($page->query('//link[contains(@href, "favicon.svg")]')->length)->toBe(0)
        ->and($page->query('//*[contains(@class, "logo-mark")]')->length)->toBe(0);
});

it('has the bar, the folding menu, the footer and the floating WhatsApp button', function () {
    $page = sitePage($this);

    expect($page->query('//header[contains(@class, "hdr")]//nav[@aria-label="Principal"]/a')->length)->toBe(4)
        ->and($page->query('//details[contains(@class, "menu-m")]/summary[@aria-label="Abrir menú"]')->length)->toBe(1)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(1)
        ->and($page->query('//a[contains(@class, "wa-float")]')->length)->toBe(1)
        ->and($page->query('//main[@id="contenido"]')->length)->toBe(1)
        ->and($page->query('//a[@href="#contenido"]')->length)->toBe(1);
});

it('takes every WhatsApp link and contact detail from the configuration', function () {
    config([
        'site.contact.whatsapp' => '5215598765432',
        'site.contact.phone' => '+52 55 9876 5432',
        'site.contact.email' => 'citas@clinica.mx',
        'site.contact.address' => 'Calle Real 10, Coyoacán, CDMX',
        'site.contact.instagram' => 'https://www.instagram.com/clinica.real',
        'site.contact.facebook' => 'https://www.facebook.com/clinica.real',
    ]);

    $page = sitePage($this);
    $whatsapp = array_values(array_filter(siteHrefs($page), fn (string $href): bool => str_contains($href, 'wa.me')));
    $footer = implode(' | ', siteTexts($page, '//footer//*[contains(@class, "ft-col")]'));

    expect($whatsapp)->toHaveCount(4)
        ->and(array_unique($whatsapp))->toBe(['https://wa.me/5215598765432'])
        ->and($footer)->toContain('Calle Real 10, Coyoacán, CDMX')
        ->and($footer)->toContain('+52 55 9876 5432')
        ->and($footer)->toContain('citas@clinica.mx')
        ->and(siteHrefs($page))->toContain('tel:+525598765432', 'mailto:citas@clinica.mx', 'https://www.instagram.com/clinica.real', 'https://www.facebook.com/clinica.real')
        ->and(implode(' ', siteHrefs($page)))->not->toContain('521234567890');
});

it('shows the opening hours of the configuration in the footer', function () {
    config(['site.contact.schedule' => [1 => ['08:00', '18:00'], 2 => ['08:00', '18:00'], 3 => ['08:00', '18:00'], 4 => ['08:00', '18:00'], 5 => ['08:00', '18:00'], 6 => ['09:00', '13:30']]]);

    expect(siteTexts(sitePage($this), '//footer//h2[.="Horarios"]/following-sibling::span'))->toBe([
        'Lunes a viernes: 8:00 a. m. – 6:00 p. m.',
        'Sábado: 9:00 a. m. – 1:30 p. m.',
        'Domingo: Cerrado',
    ]);
});

it('links the legal pages once each by their routes and has no empty link', function () {
    $hrefs = siteHrefs(sitePage($this), '//footer//a');

    expect(array_count_values($hrefs)[route('aviso-privacidad')] ?? 0)->toBe(1)
        ->and(array_count_values($hrefs)[route('terminos')] ?? 0)->toBe(1)
        ->and(siteHrefs(sitePage($this)))->not->toContain('#', '', 'https://facebook.com', 'https://www.facebook.com', 'https://instagram.com', 'https://www.instagram.com');
});

it('opens no other tab without noopener and noreferrer, and loads nothing from another site', function () {
    $page = sitePage($this);

    foreach ($page->query('//a[@target="_blank"]') as $link) {
        expect(explode(' ', $link->getAttribute('rel')))->toContain('noopener', 'noreferrer');
    }

    foreach ([...siteHrefs($page, '//link[@href]'), ...array_map(fn (DOMElement $script): string => $script->getAttribute('src'), iterator_to_array($page->query('//script[@src]')))] as $url) {
        expect($url)->not->toMatch('#^(https?:)?//(?!localhost)#');
    }
});

it('loads the shared script of the site, and every inline script carries the nonce of the policy', function () {
    $this->withVite();

    $html = $this->get(SITE_TEST_PATH)->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $page = new DOMXPath($document);
    $sources = array_map(fn (DOMElement $script): string => $script->getAttribute('src'), iterator_to_array($page->query('//script[@src]')));

    expect(array_filter($sources, fn (string $src): bool => (bool) preg_match('#/build/assets/core-[\w-]+\.js$#', $src)))->toHaveCount(1)
        ->and($page->query('//div[contains(@class, "opt")]/@data-clinic-schedule')->length)->toBe(1);

    foreach ($page->query('//script[not(@src)][not(@type="application/ld+json")]') as $inline) {
        expect($inline->getAttribute('nonce'))->not->toBe('');
    }
});
