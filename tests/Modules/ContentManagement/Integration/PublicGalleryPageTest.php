<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 026, CA5 and CA14 (TM1, TM5; A38, A41 of /analyze 026): the gallery comes in the HTML the
 * server sends — only the visible images, each one with its description as its text — with one
 * composition per number of images, an empty page instead of an error when it cannot be read, and
 * a cache of ten minutes in front of the database.
 */

beforeEach(function () {
    $this->withoutVite();
    Cache::flush();
});

function galleryPage(ContentManagementIntegrationTestCase $test): DOMXPath
{
    $html = $test->get('/galeria')->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

function galleryImages(int $count, string $status = 'visible', string $prefix = 'Foto'): void
{
    for ($number = 1; $number <= $count; $number++) {
        GalleryImageModel::query()->create([
            'url' => "https://imagenes.example.com/galeria/{$prefix}-{$number}.webp",
            'description' => "{$prefix} {$number} del consultorio",
            'status' => $status,
        ]);
    }
}

const GALLERY_TOUR = '//*[@data-gallery]';

it('serves the gallery with the bar and footer of the new site and one main heading', function () {
    $page = galleryPage($this);

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(1)
        ->and($page->query('//footer[contains(@class, "ft")]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1)
        ->and(trim($page->query('//title')->item(0)->textContent))->toBe(config('site.pages./galeria.title'));
});

it('puts every visible image in the HTML with its description as its text, and leaves the hidden ones out (abuse)', function () {
    galleryImages(5);
    galleryImages(1, 'hidden', 'Oculta');

    $page = galleryPage($this);
    $images = iterator_to_array($page->query(GALLERY_TOUR.'//img[not(ancestor::noscript)]'));
    $address = fn (DOMElement $image): string => $image->getAttribute('data-src') ?: $image->getAttribute('src');

    expect(array_map(fn (DOMElement $image): string => $image->getAttribute('alt'), $images))
        ->toBe(['Foto 1 del consultorio', 'Foto 2 del consultorio', 'Foto 3 del consultorio', 'Foto 4 del consultorio', 'Foto 5 del consultorio'])
        ->and(array_map($address, $images))->toBe(array_map(fn (int $number): string => "https://imagenes.example.com/galeria/Foto-{$number}.webp", range(1, 5)))
        ->and(array_unique(array_map(fn (DOMElement $image): string => $image->getAttribute('data-origen'), $images)))->toBe(['propia'])
        ->and($page->document->saveHTML())->not->toContain('Oculta');
});

it('loads the first photos of the tour at once and the rest only near the view, with a copy for browsers without JavaScript', function () {
    galleryImages(5);

    $page = galleryPage($this);
    $images = iterator_to_array($page->query(GALLERY_TOUR.'//img[not(ancestor::noscript)]'));
    $html = $page->document->saveHTML();

    expect(array_map(fn (DOMElement $image): bool => $image->hasAttribute('data-src'), $images))->toBe([false, false, false, true, true])
        ->and(array_map(fn (DOMElement $image): string => $image->getAttribute('loading'), array_slice($images, 0, 3)))->toBe(['lazy', 'lazy', 'lazy'])
        ->and(str_starts_with($images[3]->getAttribute('src'), 'data:image/gif'))->toBeTrue()
        ->and(substr_count($html, '<noscript>'))->toBe(2)
        ->and($html)->toContain('Foto-4.webp')
        ->and($html)->toContain('Foto-5.webp');
});

it('shows photos of the clinic in the three frames of the welcome', function () {
    $page = galleryPage($this);
    $frames = iterator_to_array($page->query('//*[contains(@class, "hero-frames")]//img'));

    expect($frames)->toHaveCount(3)
        ->and(array_unique(array_map(fn (DOMElement $image): string => $image->getAttribute('data-origen'), $frames)))->toBe(['generada'])
        ->and(array_map(fn (DOMElement $image): bool => file_exists(public_path(ltrim(parse_url($image->getAttribute('src'), PHP_URL_PATH), '/'))), $frames))->toBe([true, true, true]);
});

it('adapts the tour of the clinic to how many images there are', function (int $count, string $composition, bool $bar) {
    galleryImages($count);

    $page = galleryPage($this);

    expect($page->query(GALLERY_TOUR)->item(0)?->getAttribute('data-gallery'))->toBe($composition)
        ->and($page->query(GALLERY_TOUR.'//img[not(ancestor::noscript)]')->length)->toBe($count)
        ->and($page->query('//*[contains(@class, "hz-bar")]')->length)->toBe($bar ? 1 : 0);
})->with([
    'none' => [0, 'vacia', false],
    'one' => [1, 'fila', false],
    'two' => [2, 'fila', false],
    'three' => [3, 'recorrido', true],
]);

it('says the photos are coming and links to WhatsApp when there are none', function () {
    $page = galleryPage($this);
    $empty = GALLERY_TOUR.'[@data-gallery="vacia"]';

    expect(trim(preg_replace('/\s+/', ' ', $page->query($empty)->item(0)->textContent)))->toContain('Muy pronto añadiremos las fotos')
        ->and($page->query("{$empty}//a[starts-with(@href, 'https://wa.me/".config('site.contact.whatsapp')."')]")->length)->toBe(1);
});

it('shows a description with markup as text, never as markup (abuse)', function () {
    GalleryImageModel::query()->create(['url' => 'https://imagenes.example.com/galeria/x.webp', 'description' => '<script>alert("galeria")</script>', 'status' => 'visible']);

    $html = $this->get('/galeria')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert')
        ->and($html)->toContain('&lt;script&gt;alert(&quot;galeria&quot;)&lt;/script&gt;');
});

it('shows the empty gallery instead of an error when the images cannot be read', function () {
    Log::spy();
    Schema::drop('galery_images');

    $page = galleryPage($this);

    expect($page->query(GALLERY_TOUR.'[@data-gallery="vacia"]')->length)->toBe(1)
        ->and($page->document->saveHTML())->not->toContain('SQLSTATE')
        ->and($page->document->saveHTML())->not->toContain('galery_images');

    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context): bool => $message === 'public_gallery.load_failed' && isset($context['exception']))->once();
});

it('reads the database once in ten minutes, however many visits come', function () {
    galleryImages(2);
    $this->get('/galeria')->assertOk();

    DB::enableQueryLog();
    $this->get('/galeria')->assertOk();
    $queries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'galery_images'));
    DB::disableQueryLog();

    expect($queries)->toBeEmpty();

    galleryImages(1, 'visible', 'Nueva');
    $this->travel(9)->minutes();
    expect(galleryPage($this)->query(GALLERY_TOUR.'//img')->length)->toBe(2);

    $this->travel(2)->minutes();
    expect(galleryPage($this)->query(GALLERY_TOUR.'//img')->length)->toBe(3);
});

it('serves the previous gallery when the new landing is off', function () {
    config(['site.new_landing' => false]);

    $this->get('/galeria')->assertOk()->assertViewIs('pages.landing.galeria');
});

/*
 * Spec 026, CA10, CA17 and CA18 (edited 2026-11-15): the before and after is an illustrated 3D
 * denture with a flat drawing behind it; the page brings one button per treatment the clinic offers,
 * the text of the chosen one and a slider with a name, and no photo.
 */
const BEFORE_AFTER = '//*[@data-before-after]';

function beforeAfterSummary(string $name): string
{
    return collect(config('site.treatments'))->firstWhere('name', $name)['summary'];
}

it('puts the before and after in the page with one button per treatment, its text and a slider with a name', function () {
    $page = galleryPage($this);
    $buttons = iterator_to_array($page->query(BEFORE_AFTER.'//button[@data-ba]'));
    $slider = $page->query(BEFORE_AFTER.'//input[@type="range"]')->item(0);
    $text = fn (string $query): string => trim(preg_replace('/\s+/', ' ', $page->query(BEFORE_AFTER.$query)->item(0)?->textContent ?? ''));

    expect(array_map(fn (DOMElement $button): string => trim($button->textContent), $buttons))->toBe(['Blanqueamiento Dental', 'Ortodoncia', 'Implantes Dentales'])
        ->and(array_map(fn (DOMElement $button): string => $button->getAttribute('data-ba'), $buttons))->toBe(['white', 'braces', 'implant'])
        ->and(array_map(fn (DOMElement $button): string => $button->getAttribute('aria-pressed'), $buttons))->toBe(['true', 'false', 'false'])
        ->and(array_map(fn (DOMElement $button): string => $button->getAttribute('data-ba-summary'), $buttons))->toBe(array_map(beforeAfterSummary(...), ['Blanqueamiento Dental', 'Ortodoncia', 'Implantes Dentales']))
        ->and($slider?->getAttribute('aria-label'))->not->toBeEmpty()
        ->and([$slider?->getAttribute('min'), $slider?->getAttribute('max'), $slider?->getAttribute('value')])->toBe(['0', '100', '50'])
        ->and($text('//*[@data-ba-title]'))->toBe('Blanqueamiento Dental')
        ->and($text('//*[@data-ba-text]'))->toBe(beforeAfterSummary('Blanqueamiento Dental'))
        ->and($page->query(BEFORE_AFTER.'//canvas[@aria-hidden="true"]')->length)->toBe(1)
        ->and($page->query(BEFORE_AFTER.'//*[contains(@class, "ba-2d")][@aria-hidden="true"]')->length)->toBe(1)
        ->and($page->query(BEFORE_AFTER.'//img')->length)->toBe(0);
});

it('takes the names and texts of the before and after from the treatments of the clinic', function () {
    config(['site.treatments' => collect(config('site.treatments'))
        ->reject(fn (array $treatment): bool => $treatment['name'] === 'Ortodoncia')
        ->map(fn (array $treatment): array => $treatment['name'] === 'Blanqueamiento Dental' ? [...$treatment, 'summary' => 'Otro texto del blanqueamiento.'] : $treatment)
        ->values()->all()]);

    $page = galleryPage($this);

    expect(array_map(fn (DOMElement $button): string => trim($button->textContent), iterator_to_array($page->query(BEFORE_AFTER.'//button[@data-ba]'))))->toBe(['Blanqueamiento Dental', 'Implantes Dentales'])
        ->and(trim($page->query(BEFORE_AFTER.'//*[@data-ba-text]')->item(0)->textContent))->toBe('Otro texto del blanqueamiento.');
});
