<?php

declare(strict_types=1);

use App\Core\Http\PublicSiteSchedule;
use Illuminate\Foundation\Vite;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 026, CA11, CA13 and CA15 (TM2): what search engines and social networks read from the
 * <head> of the public site — title, description, canonical address, Open Graph, theme-color and
 * the clinic's structured data — and that a value of config/site.php cannot break out of the
 * JSON-LD script.
 */

const SEO_LEGAL_PAGES = [
    'aviso-privacidad' => '/aviso-de-privacidad',
    'terminos' => '/terminos-y-condiciones',
];

beforeEach(function () {
    $this->withoutVite();
});

function seoPage(CoreIntegrationTestCase $test, string $path): DOMXPath
{
    $html = $test->get($path)->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

function seoMeta(DOMXPath $page, string $attribute, string $name): DOMNodeList
{
    return $page->query("//head/meta[@{$attribute}=\"{$name}\"]");
}

/** The description a legal view declares in its own @section, its only source (A28). */
function seoViewDescription(string $path): string
{
    $view = ['/aviso-de-privacidad' => 'aviso-privacidad', '/terminos-y-condiciones' => 'terminos'][$path];
    $source = file_get_contents(resource_path("views/pages/landing/{$view}.blade.php"));
    preg_match("/@section\('description', '([^']+)'\)/", $source, $match);

    return $match[1];
}

/**
 * @return list<mixed>
 */
function seoStructuredData(DOMXPath $page): array
{
    return array_map(
        fn (DOMElement $script): mixed => json_decode($script->textContent, true, flags: JSON_THROW_ON_ERROR),
        iterator_to_array($page->query('//script[@type="application/ld+json"]')),
    );
}

it('declares the legal page in Spanish with one canonical address and one description from its view', function (string $path) {
    $page = seoPage($this, $path);
    $canonical = $page->query('//head/link[@rel="canonical"]');
    $description = seoMeta($page, 'name', 'description');

    expect($page->query('//html[@lang="es"]')->length)->toBe(1)
        ->and($canonical->length)->toBe(1)
        ->and($canonical->item(0)->getAttribute('href'))->toBe('https://dentissapp.com'.$path)
        ->and($description->length)->toBe(1)
        ->and($description->item(0)->getAttribute('content'))->toBe(seoViewDescription($path));
})->with(SEO_LEGAL_PAGES);

it('describes the legal page for social networks with the same title, description and address', function (string $path) {
    $page = seoPage($this, $path);
    $content = fn (string $property): string => seoMeta($page, 'property', $property)->item(0)?->getAttribute('content') ?? '';

    expect($content('og:url'))->toBe($page->query('//head/link[@rel="canonical"]')->item(0)->getAttribute('href'))
        ->and($content('og:title'))->toBe($page->query('//title')->item(0)->textContent)
        ->and($content('og:description'))->toBe(seoViewDescription($path))
        ->and($content('og:type'))->toBe('website')
        ->and($content('og:locale'))->toBe('es_MX')
        ->and($content('og:site_name'))->toBe('Dentissa');
})->with(SEO_LEGAL_PAGES);

it('paints the browser bar with the primary colour of the design tokens', function (string $path) {
    preg_match('/--color-primary:\s*(#[0-9a-fA-F]{6})/', file_get_contents(resource_path('css/app.css')), $token);
    $theme = seoMeta(seoPage($this, $path), 'name', 'theme-color');

    expect($theme->length)->toBe(1)
        ->and(strtolower($theme->item(0)->getAttribute('content')))->toBe(strtolower($token[1]));
})->with(SEO_LEGAL_PAGES);

it('publishes no structured data on the legal pages', function (string $path) {
    expect(seoPage($this, $path)->query('//script[@type="application/ld+json"]')->length)->toBe(0);
})->with(SEO_LEGAL_PAGES);

it('keeps the canonical address without a trailing slash or the query string', function () {
    $canonical = seoPage($this, '/aviso-de-privacidad?utm_source=prueba')->query('//head/link[@rel="canonical"]')->item(0);

    expect($canonical->getAttribute('href'))->toBe('https://dentissapp.com/aviso-de-privacidad')
        ->and(seoPage($this, '/')->query('//head/link[@rel="canonical"]')->item(0)->getAttribute('href'))->toBe('https://dentissapp.com');
});

it('keeps a value of config/site.php from closing the structured data script', function () {
    config(['site.name' => 'Dentissa</script><script>window.inyectado = true</script>']);

    $html = $this->get('/')->assertOk()->getContent();
    $page = new DOMDocument;
    @$page->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $data = seoStructuredData(new DOMXPath($page));

    expect($data)->toHaveCount(1)
        ->and($data[0]['@type'])->toBe('Dentist')
        ->and($data[0]['name'])->toBe('Dentissa</script><script>window.inyectado = true</script>')
        ->and($html)->not->toContain('<script>window.inyectado');
});

it('publishes only the name and web address of the clinic while its contact data is provisional', function () {
    config(['site.provisional' => array_fill_keys(array_keys(config('site.provisional')), true)]);

    $data = seoStructuredData(seoPage($this, '/'));

    expect($data[0])->toBe(['@context' => 'https://schema.org', '@type' => 'Dentist', 'name' => 'Dentissa', 'url' => 'https://dentissapp.com']);
});

it('preloads the two fonts of the first screen', function () {
    $this->swap(Vite::class, new Vite);

    $fonts = seoPage($this, '/aviso-de-privacidad')->query('//head/link[@rel="preload"][@as="font"]');
    $hrefs = array_map(fn (DOMElement $link): string => $link->getAttribute('href'), iterator_to_array($fonts));

    expect($fonts)->toHaveCount(2)
        ->and($hrefs[0])->toContain('/build/assets/inter-tight-latin-')
        ->and($hrefs[1])->toContain('/build/assets/instrument-serif-italic-latin-')
        ->and($fonts->item(0)->getAttribute('type'))->toBe('font/woff2')
        ->and($fonts->item(0)->hasAttribute('crossorigin'))->toBeTrue();
});

it('serves a sitemap with the six indexable pages as absolute addresses without a trailing slash', function () {
    $response = $this->get('/sitemap.xml')->assertOk();
    $sitemap = new DOMDocument;

    expect($response->headers->get('Content-Type'))->toStartWith('application/xml')
        ->and($sitemap->loadXML($response->getContent()))->toBeTrue()
        ->and($sitemap->documentElement->namespaceURI)->toBe('http://www.sitemaps.org/schemas/sitemap/0.9');

    $locations = array_map(fn (DOMElement $loc): string => $loc->textContent, iterator_to_array($sitemap->getElementsByTagName('loc')));

    expect($locations)->toBe([
        'https://dentissapp.com',
        'https://dentissapp.com/acerca-de-nosotros',
        'https://dentissapp.com/galeria',
        'https://dentissapp.com/contacto',
        'https://dentissapp.com/aviso-de-privacidad',
        'https://dentissapp.com/terminos-y-condiciones',
    ]);
});

it('lists in the sitemap only what config/site.php marks as indexable', function () {
    config(['site.indexable' => ['/', '/galeria']]);

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect(substr_count($body, '<loc>'))->toBe(2)
        ->and($body)->not->toContain('/login')
        ->and($body)->not->toContain('/agenda')
        ->and($body)->not->toContain('/dashboard');
});

it('declares the sitemap in robots.txt and blocks nothing', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://dentissapp.com/sitemap.xml')
        ->and($robots)->not->toMatch('/^Disallow:\s*\/\s*$/m');
});

const SEO_SITE_PAGES = ['/', '/acerca-de-nosotros', '/galeria', '/contacto', '/aviso-de-privacidad', '/terminos-y-condiciones'];

/** The contact data of a clinic that already gave its real values. */
function seoRealContact(): void
{
    config([
        'site.provisional' => array_fill_keys(array_keys(config('site.provisional')), false),
        'site.contact.phone' => '+52 753 111 2233',
        'site.contact.address' => 'Av. Lázaro Cárdenas 123, Centro',
        'site.contact.schedule' => [1 => ['09:00', '19:00'], 2 => ['09:00', '19:00'], 3 => ['09:00', '19:00'], 4 => ['09:00', '19:00'], 5 => ['09:00', '19:00'], 6 => ['09:00', '14:00']],
    ]);
}

it('gives each of the six pages its own title and description', function () {
    $heads = array_map(function (string $path): array {
        $page = seoPage($this, $path);

        return [$page->query('//title')->item(0)->textContent, seoMeta($page, 'name', 'description')->item(0)->getAttribute('content')];
    }, SEO_SITE_PAGES);

    $titles = array_column($heads, 0);
    $descriptions = array_column($heads, 1);

    expect(array_unique($titles))->toHaveCount(6)
        ->and(array_unique($descriptions))->toHaveCount(6);

    foreach ($descriptions as $description) {
        expect(mb_strlen($description))->toBeGreaterThanOrEqual(70)->toBeLessThanOrEqual(160);
    }
});

it('declares each page of the site in Spanish with a single main heading and its canonical address', function (string $path) {
    $page = seoPage($this, $path);

    expect($page->query('//html[@lang="es"]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1)
        ->and($page->query('//head/link[@rel="canonical"]')->item(0)->getAttribute('href'))->toBe(rtrim('https://dentissapp.com'.$path, '/'))
        ->and(seoMeta($page, 'property', 'og:title')->item(0)->getAttribute('content'))->toBe(config("site.pages.{$path}.title"))
        ->and(seoMeta($page, 'name', 'description')->item(0)->getAttribute('content'))->toBe(config("site.pages.{$path}.description"));
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto']);

it('publishes the structured data of the clinic only on the home and contact pages', function (string $path, int $count) {
    seoRealContact();

    $data = seoStructuredData(seoPage($this, $path));

    expect($data)->toHaveCount($count);

    if ($count === 1) {
        expect($data[0]['@type'])->toBe('Dentist');
    }
})->with([
    'inicio' => ['/', 1],
    'acerca' => ['/acerca-de-nosotros', 0],
    'galeria' => ['/galeria', 0],
    'contacto' => ['/contacto', 1],
]);

it('publishes the name, address, phone and hours the contact page shows', function (string $path) {
    seoRealContact();

    $page = seoPage($this, $path);
    $text = preg_replace('/\s+/', ' ', seoPage($this, '/contacto')->query('//body')->item(0)->textContent);
    $data = seoStructuredData($page)[0];
    $published = [];

    foreach ($data['openingHoursSpecification'] as $specification) {
        foreach ($specification['dayOfWeek'] as $day) {
            $published[$day] = [$specification['opens'], $specification['closes']];
        }
    }

    expect($data['name'])->toBe('Dentissa')
        ->and($data['url'])->toBe('https://dentissapp.com')
        ->and($data['address']['streetAddress'])->toBe('Av. Lázaro Cárdenas 123, Centro')
        ->and($text)->toContain('Av. Lázaro Cárdenas 123, Centro')
        ->and($data['telephone'])->toBe('+52 753 111 2233')
        ->and($text)->toContain('+52 753 111 2233')
        ->and($published)->toBe(['Monday' => ['09:00', '19:00'], 'Tuesday' => ['09:00', '19:00'], 'Wednesday' => ['09:00', '19:00'], 'Thursday' => ['09:00', '19:00'], 'Friday' => ['09:00', '19:00'], 'Saturday' => ['09:00', '14:00']]);

    foreach (PublicSiteSchedule::fromConfig()->lines() as $line) {
        expect($text)->toContain($line['hours']);
    }
})->with(['/', '/contacto']);

it('leaves out of the structured data each contact value that is still provisional', function () {
    seoRealContact();
    config(['site.provisional.phone' => true, 'site.provisional.schedule' => true]);

    $data = seoStructuredData(seoPage($this, '/contacto'))[0];

    expect($data)->toHaveKey('address')
        ->and($data)->not->toHaveKey('telephone')
        ->and($data)->not->toHaveKey('openingHoursSpecification');
});
