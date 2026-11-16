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

    expect(array_filter($whatsapp, fn (string $href): bool => ! str_contains($href, '?')))->toHaveCount(4)
        ->and(array_filter($whatsapp, fn (string $href): bool => str_contains($href, '?text=')))->toHaveCount(count(config('site.treatments')) + 1)
        ->and(array_values(array_unique(array_map(fn (string $href): string => strtok($href, '?'), $whatsapp))))->toBe(['https://wa.me/5215598765432'])
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

    foreach ([...siteHrefs($page, '//link[@href][not(@rel="canonical")]'), ...array_map(fn (DOMElement $script): string => $script->getAttribute('src'), iterator_to_array($page->query('//script[@src]')))] as $url) {
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

it('serves the legal pages with the bar and footer of the new site, keeping their text', function (string $path, string $title) {
    $page = sitePage($this, $path);

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(1)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(1)
        ->and(siteTexts($page, '//h1'))->toBe([$title])
        ->and(siteTexts($page, '//title'))->toBe(["{$title} - Dentissa"])
        ->and($page->query('//meta[@name="description"]')->length)->toBe(1);
})->with([
    'privacy notice' => ['/aviso-de-privacidad', 'Aviso de Privacidad'],
    'terms' => ['/terminos-y-condiciones', 'Términos y Condiciones'],
]);

it('serves the legal pages with the previous layout when the new landing is off', function (string $path) {
    config(['site.new_landing' => false]);

    $page = sitePage($this, $path);

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(0)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(0)
        ->and($page->query('//*[@id="mobile-menu-btn"]')->length)->toBe(1);
})->with(['/aviso-de-privacidad', '/terminos-y-condiciones']);

it('serves "Acerca de nosotros" with the new site, one main heading and its own title', function () {
    $page = sitePage($this, '/acerca-de-nosotros');

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(1)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1)
        ->and(siteTexts($page, '//title'))->toBe([config('site.pages./acerca-de-nosotros.title')])
        ->and(array_map(fn (DOMElement $meta): string => $meta->getAttribute('content'), iterator_to_array($page->query('//meta[@name="description"]'))))
        ->toBe([config('site.pages./acerca-de-nosotros.description')]);
});

it('takes the WhatsApp link, the address and the hours of "Acerca de nosotros" from the configuration', function () {
    config([
        'site.contact.whatsapp' => '5215598765432',
        'site.contact.address' => 'Calle de Prueba 45, Colonia Norte',
    ]);

    $page = sitePage($this, '/acerca-de-nosotros');
    $main = '//main';
    $whatsappLinks = array_filter(siteHrefs($page, "{$main}//a"), fn (string $href): bool => str_contains($href, 'wa.me'));

    expect($whatsappLinks)->not->toBeEmpty()
        ->and(array_unique(array_map(fn (string $href): string => strtok($href, '?'), $whatsappLinks)))->toBe(['https://wa.me/5215598765432'])
        ->and(implode(' ', siteTexts($page, $main)))->toContain('Calle de Prueba 45, Colonia Norte')
        ->not->toContain('521234567890')
        ->not->toContain('Av. Universidad 1200')
        ->toContain('Claro, los sábados abrimos de 9:00 a. m. – 2:00 p. m.')
        ->not->toContain('..');
});

it('presents each person of the team by name and a phrase of their own, without buttons or years', function () {
    $page = sitePage($this, '/acerca-de-nosotros');
    $cards = '//main//*[contains(concat(" ", normalize-space(@class), " "), " flip ")]';

    expect($page->query($cards)->length)->toBe(3)
        ->and($page->query("{$cards}//a")->length)->toBe(0)
        ->and($page->query("{$cards}//blockquote")->length)->toBe(3)
        ->and(implode(' ', siteTexts($page, $cards)))->not->toContain('años de experiencia');
});

it('serves "Acerca de nosotros" with the previous page when the new landing is off', function () {
    config(['site.new_landing' => false]);

    $this->get('/acerca-de-nosotros')->assertOk()->assertViewIs('pages.landing.acerca');
});

it('names the city of the clinic from config and never Mexico City (amendment of CA7)', function (string $path) {
    config(['site.city' => 'Ciudad de Prueba']);

    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->not->toContain('Ciudad de México');
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto']);

it('says the city of the clinic in the label of the welcome', function () {
    config(['site.city' => 'Ciudad de Prueba']);

    expect(siteTexts(sitePage($this, '/'), '//main//*[contains(@class, "eyebrow")]')[0] ?? '')->toBe('Odontología integral · Ciudad de Prueba');
});

it('highlights "sonrisa" in the heading of the services like the other headings of the home', function () {
    $page = sitePage($this, '/');

    expect(siteTexts($page, '//main//h2[contains(., "Todo lo que tu")]//*[contains(concat(" ", normalize-space(@class), " "), " hl ")]'))->toBe(['sonrisa'])
        ->and($page->query('//main//h2[contains(., "Todo lo que tu")]//*[contains(@class, "hl")]/*[local-name()="svg"][@aria-hidden="true"]')->length)->toBe(1);
});

it('shows the real logo of the clinic in the WhatsApp chat of the closing call', function (string $path) {
    $page = sitePage($this, $path);
    $avatar = '//main//*[contains(concat(" ", normalize-space(@class), " "), " ph-av ")]';
    $logo = $page->query("{$avatar}/img")->item(0);

    expect($page->query($avatar)->length)->toBe(1)
        ->and($page->query("{$avatar}//*[local-name()='svg']")->length)->toBe(0)
        ->and(parse_url($logo?->getAttribute('src') ?? '', PHP_URL_PATH))->toBe('/images/brand/icon-64.png')
        ->and($logo?->getAttribute('alt'))->toBe('Logo de Dentissa')
        ->and($logo?->getAttribute('data-origen'))->toBe('propia');
})->with(['/', '/acerca-de-nosotros', '/galeria']);

it('draws the arrows of its links as pictures, never as characters a phone may turn into an emoji', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->not->toMatch('/[\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}]/u');
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto']);

/*
 * T040 (refined 2026-11-15): the phone menu redesigned by the user. It is still a <details>, so it
 * opens without JavaScript; every address and text of it comes from config/site.php.
 */
it('brings the redesigned phone menu with the pages, the treatments and the clinic data from config', function () {
    config([
        'site.contact.whatsapp' => '5215598765432',
        'site.contact.phone' => '+52 753 111 2233',
        'site.contact.instagram' => 'https://www.instagram.com/clinica.prueba',
        'site.contact.facebook' => 'https://www.facebook.com/clinica.prueba',
    ]);

    $page = sitePage($this, '/galeria');
    $panel = '//details[contains(@class, "menu-m")]//*[contains(@class, "menu-panel")]';
    $tiles = iterator_to_array($page->query("{$panel}//a[contains(@class, \"mn-tile\")]"));

    expect($page->query('//details[contains(@class, "menu-m")]/summary[@aria-label="Abrir menú"]/span')->length)->toBe(3)
        ->and(siteTexts($page, "{$panel}//nav//a"))->toBe(['Inicio', 'Acerca de Nosotros', 'Galería', 'Contacto'])
        ->and(siteTexts($page, "{$panel}//nav//a[@aria-current=\"page\"]"))->toBe(['Galería'])
        ->and(siteTexts($page, "{$panel}//nav//a/span[contains(concat(\" \", @class, \" \"), \" hl \")]"))->toBe(['Galería'])
        ->and($page->query("{$panel}//nav//a[@aria-current=\"page\"]/span[@class=\"hl\"]")->length)->toBe(1)
        ->and(array_map(fn (DOMElement $tile): string => trim($tile->textContent), $tiles))->toBe([...array_column(config('site.treatments'), 'name'), 'Valoración general'])
        ->and(array_unique(array_map(fn (DOMElement $tile): string => strtok($tile->getAttribute('href'), '?'), $tiles)))->toBe(['https://wa.me/5215598765432'])
        ->and(array_filter($tiles, fn (DOMElement $tile): bool => ! str_contains($tile->getAttribute('href'), '?text=')))->toBe([])
        ->and($page->query("{$panel}//a[@href=\"https://www.instagram.com/clinica.prueba\"][@aria-label=\"Instagram\"]")->length)->toBe(1)
        ->and($page->query("{$panel}//a[@href=\"https://www.facebook.com/clinica.prueba\"][@aria-label=\"Facebook\"]")->length)->toBe(1)
        ->and($page->query("{$panel}//a[@href=\"tel:+527531112233\"]")->length)->toBe(1)
        ->and($page->query("{$panel}//a[starts-with(@href, \"https://wa.me/5215598765432\")][contains(., \"Agendar cita\")]")->length)->toBe(1)
        ->and($page->query("{$panel}//a[contains(@href, \"/login\")]")->length)->toBe(1)
        ->and(siteTexts($page, "{$panel}//*[@data-menu-status]")[0] ?? '')->toContain('9:00');
});

/*
 * T022 (CA1, CA6, CA7; A35): the contact page of the proposal. The form only composes a WhatsApp
 * message in the browser: it has no address to post to nor a CSRF token, and it is hidden until
 * JavaScript runs, while the direct WhatsApp link is in the HTML for everyone. Every contact
 * detail, the map links and the weekly hours come from config/site.php.
 */
it('serves the contact page with the new site, one main heading and its own title', function () {
    $page = sitePage($this, '/contacto');

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(1)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1)
        ->and(siteTexts($page, '//title'))->toBe([config('site.pages./contacto.title')])
        ->and(array_map(fn (DOMElement $meta): string => $meta->getAttribute('content'), iterator_to_array($page->query('//meta[@name="description"]'))))
        ->toBe([config('site.pages./contacto.description')]);
});

it('composes the message in a form that cannot post to the server, with a direct WhatsApp link for everyone', function () {
    config(['site.contact.whatsapp' => '5215598765432']);

    $page = sitePage($this, '/contacto');
    $forms = iterator_to_array($page->query('//main//form'));
    $css = file_get_contents(resource_path('css/site/pages/contacto.css'));

    expect($forms)->toHaveCount(1)
        ->and($forms[0]->hasAttribute('action'))->toBeFalse()
        ->and($forms[0]->hasAttribute('method'))->toBeFalse()
        ->and($forms[0]->getAttribute('class'))->toContain('ct-form')
        ->and($page->query('//main//form//input[@name="_token"]')->length)->toBe(0)
        ->and($page->query('//main//form//textarea[@id="ct-msg"]')->length)->toBe(1)
        ->and(siteTexts($page, '//main//form//button[@data-motivo]'))->toContain('Valoración general', 'Otro')
        ->and($css)->toContain('html:not(.js) .ct-form{display:none}')
        ->and($css)->toContain('.hl svg path{stroke:var(--color-secondary)}')
        ->and($page->query('//main//*[contains(@class, "ct-direct")]//a[@href="https://wa.me/5215598765432"]')->length)->toBe(1);
});

it('takes the phone, the email, the networks, the address and the map links of the contact page from config', function () {
    config([
        'site.contact.whatsapp' => '5215598765432',
        'site.contact.phone' => '+52 753 111 2233',
        'site.contact.email' => 'citas@clinica.mx',
        'site.contact.address' => 'Calle de Prueba 45, Colonia Norte',
        'site.contact.maps_url' => 'https://maps.example.test/clinica',
        'site.contact.instagram' => 'https://www.instagram.com/clinica.prueba',
        'site.contact.facebook' => 'https://www.facebook.com/clinica.prueba',
    ]);

    $page = sitePage($this, '/contacto');
    $main = implode(' ', siteTexts($page, '//main'));
    $hrefs = siteHrefs($page, '//main//a');

    expect($hrefs)->toContain('tel:+527531112233', 'mailto:citas@clinica.mx', 'https://www.instagram.com/clinica.prueba', 'https://www.facebook.com/clinica.prueba', 'https://maps.example.test/clinica')
        ->and($hrefs)->toContain('https://waze.com/ul?q='.rawurlencode('Calle de Prueba 45, Colonia Norte').'&navigate=yes')
        ->and(array_values(array_unique(array_map(fn (string $href): string => strtok($href, '?'), array_filter($hrefs, fn (string $href): bool => str_contains($href, 'wa.me'))))))->toBe(['https://wa.me/5215598765432'])
        ->and($main)->toContain('+52 753 111 2233', 'citas@clinica.mx', 'Calle de Prueba 45, Colonia Norte', '@clinica.prueba')
        ->and($page->query('//main//*[@data-copy="+52 753 111 2233"]')->length)->toBe(1)
        ->and($page->query('//main//*[@data-copy="Calle de Prueba 45, Colonia Norte"]')->length)->toBe(1)
        ->and($page->query('//main//*[@role="img"][contains(@aria-label, "Calle de Prueba 45, Colonia Norte")]')->length)->toBe(1)
        ->and($main.' '.implode(' ', $hrefs))->not->toContain('Av. Universidad', '521234567890', '[CORREO', 'Ciudad de Mexico', '55 1234 5678');
});

it('lists the weekly hours of the contact page from config, with the closed days', function () {
    config(['site.contact.schedule' => [1 => ['08:00', '18:00'], 2 => ['08:00', '18:00'], 3 => ['08:00', '18:00'], 4 => ['08:00', '18:00'], 5 => ['08:00', '14:00'], 6 => ['09:00', '13:30']]]);

    $page = sitePage($this, '/contacto');
    $rows = '//main//*[contains(@class, "wk-row")]';

    expect(siteTexts($page, "{$rows}/*[contains(concat(\" \", @class, \" \"), \" wk-t \")]"))->toBe([
        '8:00 a. m. – 6:00 p. m.', '8:00 a. m. – 6:00 p. m.', '8:00 a. m. – 6:00 p. m.', '8:00 a. m. – 6:00 p. m.',
        '8:00 a. m. – 2:00 p. m.', '9:00 a. m. – 1:30 p. m.', 'Cerrado',
    ])
        ->and(array_map(fn (DOMElement $row): string => $row->getAttribute('data-day'), iterator_to_array($page->query($rows))))->toBe(['1', '2', '3', '4', '5', '6', '0'])
        ->and($page->query("{$rows}[@data-day=\"0\"][contains(@class, \"closed\")]")->length)->toBe(1)
        ->and($page->query("{$rows}[@data-day=\"6\"]//i[contains(@class, \"wk-bar\")]/@style")->item(0)?->nodeValue)->toBe('--from: 8.33%; --span: 37.5%; --i: 5');
});

it('opens no other tab from the contact page without noopener and noreferrer', function () {
    $links = iterator_to_array(sitePage($this, '/contacto')->query('//a[@target="_blank"]'));

    expect($links)->not->toBeEmpty();

    foreach ($links as $link) {
        expect(explode(' ', $link->getAttribute('rel')))->toContain('noopener', 'noreferrer');
    }
});

it('serves the previous contact page when the new landing is off', function () {
    config(['site.new_landing' => false]);

    $this->get('/contacto')->assertOk()->assertViewIs('pages.landing.contacto');
});

/*
 * T025 (CA17): the site only names the treatments Dentissa offers, the list of config/site.php,
 * in the tour of the home, the services and the selector of the before and after; no page names
 * one of the treatments the clinic does not offer. Every link of the six public routes that opens
 * another tab carries noopener and noreferrer (TM4).
 */
const SITE_PAGES_DISCARDED_TREATMENTS = ['endodoncia', 'periodoncia', 'carilla', 'corona', 'prótesis', 'protesis', 'diseño de sonrisa', 'cirugía maxilofacial', 'bruxismo'];

it('names only the treatments of the list in the tour, the services, the before and after and the motives', function () {
    $names = array_column(config('site.treatments'), 'name');
    $home = sitePage($this, '/');
    $gallery = sitePage($this, '/galeria');
    $contact = sitePage($this, '/contacto');
    $selector = siteTexts($gallery, '//main//button[@data-ba]');
    $motives = array_values(array_diff(siteTexts($contact, '//main//button[@data-motivo]'), ['Valoración general', 'Otro']));

    expect(siteTexts($home, '//main//*[contains(@class, "tour-step")]/h2'))->toBe(array_slice($names, 0, 4))
        ->and(siteTexts($home, '//main//*[contains(@class, "sv-name")]'))->toBe($names)
        ->and($selector)->not->toBeEmpty()
        ->and(array_diff($selector, $names))->toBe([])
        ->and($motives)->toBe($names);
});

it('names no treatment the clinic does not offer on any page', function (string $path) {
    $text = mb_strtolower(implode(' ', siteTexts(sitePage($this, $path), '//body')));

    expect(array_values(array_filter(SITE_PAGES_DISCARDED_TREATMENTS, fn (string $term): bool => str_contains($text, $term))))->toBe([]);
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto', '/aviso-de-privacidad', '/terminos-y-condiciones']);

it('opens no other tab from a public page without noopener and noreferrer', function (string $path) {
    foreach (sitePage($this, $path)->query('//a[@target="_blank"]') as $link) {
        expect(explode(' ', $link->getAttribute('rel')))->toContain('noopener', 'noreferrer');
    }
})->with(['/', '/acerca-de-nosotros', '/galeria', '/contacto', '/aviso-de-privacidad', '/terminos-y-condiciones']);
