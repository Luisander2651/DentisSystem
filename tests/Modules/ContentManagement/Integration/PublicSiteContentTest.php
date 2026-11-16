<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models\CertificationModel;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 026, CA1, CA2, CA3, CA4, CA14, CA15 and CA17 (TM1, TM2; A15, A41, A46 of /analyze 026): the
 * home page of the new site shows what the staff publishes — current promotions, certifications
 * and testimonials — with the empty state of each section, survives a failure to read it, escapes
 * every text, and names only the treatments of config/site.php.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00', 'America/Mexico_City'));
});

function homePage(ContentManagementIntegrationTestCase $test): DOMXPath
{
    $html = $test->get('/')->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

function homeText(DOMXPath $page, string $query): string
{
    return implode(' | ', array_map(fn (DOMNode $node): string => trim(preg_replace('/\s+/', ' ', $node->textContent)), iterator_to_array($page->query($query))));
}

function homePromotion(string $name, string $end, string $status = 'visible', string $description = 'Descripción de prueba'): void
{
    PromotionModel::query()->create(['name' => $name, 'description' => $description, 'status' => $status, 'discount_percentage' => '15', 'start_date' => '2026-11-01', 'end_date' => $end]);
}

function homeTestimonials(int $count, string $status = 'visible'): void
{
    foreach (range(1, $count) as $number) {
        TestimonialModel::query()->create(['author' => "Paciente {$number}", 'description' => "Opinión número {$number} sobre la clínica.", 'status' => $status]);
    }
}

it('serves the new home with the bar and footer of the site', function () {
    $page = homePage($this);

    expect($page->query('//header[contains(@class, "hdr")]')->length)->toBe(1)
        ->and($page->query('//h1')->length)->toBe(1)
        ->and(homeText($page, '//h1'))->toBe('La mejor tecnología para tu salud dental')
        ->and($page->query('//section[@id="explora"]')->length)->toBe(1);
});

it('serves the previous home when the new landing is off', function () {
    config(['site.new_landing' => false]);

    $this->get('/')->assertOk()->assertViewIs('pages.landing.inicio');
});

it('shows only the coupon when there is one current promotion', function () {
    homePromotion('Limpieza a mitad de precio', '2026-11-30');

    $page = homePage($this);

    expect(homeText($page, '//*[@data-pm="title"]'))->toBe('Limpieza a mitad de precio')
        ->and($page->query('//*[@data-pm-more]')->length)->toBe(0)
        ->and($page->query('//*[@data-pm-empty]')->length)->toBe(0)
        ->and($page->query('//*[@data-pm-card][@data-start="2026-11-01"][@data-end="2026-11-30"]')->length)->toBe(1);
});

it('leads with the promotion that ends first and lists the others as tickets', function () {
    homePromotion('Termina el 20', '2026-11-20');
    homePromotion('Termina el 15', '2026-11-15');
    homePromotion('Termina el 30', '2026-11-30');

    $page = homePage($this);

    expect(homeText($page, '//*[@data-pm="title"]'))->toBe('Termina el 15')
        ->and(homeText($page, '//*[@data-pm="of"]'))->toBe('1 de 3 activas')
        ->and($page->query('//button[@data-promo]')->length)->toBe(3)
        ->and($page->query('//button[@data-promo][not(@hidden)]')->length)->toBe(2);
});

it('says there are no promotions when none is current, and never shows a hidden or expired one (abuse)', function () {
    homePromotion('Promoción oculta', '2026-11-30', 'oculto');
    homePromotion('Promoción vencida', '2026-11-11');

    $page = homePage($this);
    $html = $page->document->saveHTML();

    expect($page->query('//*[@data-pm-empty]')->length)->toBe(1)
        ->and(homeText($page, '//*[@data-pm-empty]//h2'))->toBe('Por ahora no hay promociones activas')
        ->and($page->query('//*[@data-pm-empty]//a[starts-with(@href, "https://wa.me/")][contains(@href, "?text=")][contains(., "Pregúntanos por WhatsApp")]')->length)->toBe(1)
        ->and($page->query('//*[@data-pm-empty]//a')->length)->toBe(1)
        ->and(homeText($page, '//*[@data-pm-empty]'))->not->toContain('Ver tratamientos')
        ->and($page->query('//*[@data-pm-empty]//*[contains(@class, "pm-none-art")][@aria-hidden="true"]')->length)->toBe(1)
        ->and(homeText($page, '//*[@data-pm-empty]'))->not->toContain('te avisamos')
        ->and($html)->not->toContain('Promoción oculta')
        ->and($html)->not->toContain('Promoción vencida');
});

it('shows each visible certification with its description in the place of the institution', function () {
    CertificationModel::query()->create(['name' => 'Certificación en ortodoncia', 'description' => 'Colegio Mexicano de Ortodoncia', 'status' => 'visible', 'date' => '2024-03-10', 'image_url' => '/storage/certificaciones/ortodoncia.png']);
    CertificationModel::query()->create(['name' => 'Certificación oculta', 'description' => 'Instituto', 'status' => 'oculto', 'date' => '2024-03-10', 'image_url' => '/storage/certificaciones/oculta.png']);

    $page = homePage($this);

    expect($page->query('//section[@id="certificaciones"]//article[contains(@class, "cf")]')->length)->toBe(1)
        ->and(homeText($page, '//*[contains(@class, "cf-meta")]'))->toContain('Certificación en ortodoncia', 'Colegio Mexicano de Ortodoncia · 10 mar. 2024')
        ->and($page->query('//*[contains(@class, "cf-mat")]/img[@src="/storage/certificaciones/ortodoncia.png"][@data-origen="propia"]')->length)->toBe(1)
        ->and($page->document->saveHTML())->not->toContain('Certificación oculta');
});

it('leaves the certifications section out when there are none', function () {
    expect(homePage($this)->query('//section[@id="certificaciones"]')->length)->toBe(0);
});

it('invites to leave an opinion by WhatsApp when there are no testimonials', function () {
    $page = homePage($this);

    expect(homeText($page, '//*[contains(@class, "ts-empty")]'))->toContain('¿Ya nos visitaste?')
        ->and($page->query('//*[contains(@class, "ts-empty")]//a[starts-with(@href, "https://wa.me/")]')->length)->toBe(1);
});

it('adapts the testimonials to how many there are', function (int $count, bool $carousel, bool $ribbon) {
    homeTestimonials($count);
    homeTestimonials(1, 'oculto');

    $page = homePage($this);

    expect($page->query('//*[@data-ts-slide]')->length)->toBe($count)
        ->and($page->query('//*[contains(@class, "ts-nav")]')->length)->toBe($carousel ? 1 : 0)
        ->and($page->query('//*[contains(@class, "ts-dots")]')->length)->toBe($carousel ? 1 : 0)
        ->and($page->query('//*[contains(@class, "ts-marq")]')->length)->toBe($ribbon ? 1 : 0)
        ->and($page->query('//*[contains(@class, "stars")]')->length)->toBe(0);
})->with([
    'one: no carousel' => [1, false, false],
    'two: carousel' => [2, true, false],
    'three: carousel without ribbon' => [3, true, false],
    'four: carousel and ribbon' => [4, true, true],
]);

it('shows published content as text, never as markup (abuse)', function () {
    homePromotion('<script>alert("promo")</script>', '2026-11-30', description: '<img src=x onerror=alert(1)>');
    TestimonialModel::query()->create(['author' => '<b>Autor</b>', 'description' => '</blockquote><script>alert(2)</script>', 'status' => 'visible']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert')
        ->and($html)->not->toContain('<img src=x')
        ->and($html)->not->toContain('<b>Autor</b>')
        ->and($html)->toContain('&lt;script&gt;alert(&quot;promo&quot;)&lt;/script&gt;');
});

it('shows the empty states instead of an error when the content cannot be read', function () {
    Log::spy();
    Schema::drop('promotions');

    $page = homePage($this);

    expect($page->query('//*[@data-pm-empty]')->length)->toBe(1)
        ->and($page->query('//*[contains(@class, "ts-empty")]')->length)->toBe(1)
        ->and($page->document->saveHTML())->not->toContain('SQLSTATE');

    Log::shouldHaveReceived('error')->withArgs(fn (string $message): bool => $message === 'Unable to load landing page content')->once();
});

it('stops showing a promotion that ended at the clinic\'s midnight, cache included', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 23:55', 'America/Mexico_City'));
    homePromotion('Termina hoy', '2026-11-12');

    expect(homeText(homePage($this), '//*[@data-pm="title"]'))->toBe('Termina hoy');

    $this->travelTo(CarbonImmutable::parse('2026-11-13 00:01', 'America/Mexico_City'));

    expect(homePage($this)->query('//*[@data-pm-empty]')->length)->toBe(1);
});

it('names in the tour, the ribbon and the services only the treatments of the clinic', function () {
    $page = homePage($this);
    $treatments = array_column(config('site.treatments'), 'name');

    expect(array_map('trim', array_map(fn (DOMNode $node): string => $node->textContent, iterator_to_array($page->query('//*[contains(@class, "sv-name")]')))))->toBe($treatments)
        ->and(array_map(fn (DOMNode $node): string => trim($node->textContent), iterator_to_array($page->query('//*[contains(@class, "tour-step")]/h2'))))->toBe(array_slice($treatments, 0, 4));
});

it('marks the photos of the proposal as generated and gives every image its size', function () {
    $page = homePage($this);

    foreach ($page->query('//section[@id="explora"]//img | //figure[contains(@class, "fq-photo")]//img') as $image) {
        expect($image->getAttribute('data-origen'))->toBe('generada')
            ->and($image->getAttribute('width'))->not->toBe('')
            ->and($image->getAttribute('height'))->not->toBe('');
    }

    expect($page->query('//img[@fetchpriority="high"]')->length)->toBe(1);
});

it('never serves the promotions of one day on another, whatever was cached first', function () {
    homePromotion('Solo en noviembre', '2026-11-30');

    $this->travelTo(CarbonImmutable::parse('2026-12-05 12:00', 'America/Mexico_City'));
    expect(homePage($this)->query('//*[@data-pm-empty]')->length)->toBe(1);

    $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00', 'America/Mexico_City'));
    expect(homeText(homePage($this), '//*[@data-pm="title"]'))->toBe('Solo en noviembre');
});

it('serves the tour with its four treatments and a still tooth, and no canvas until the script draws one (CA9)', function () {
    $page = homePage($this);
    $still = $page->query('//section[@data-ref="Tour"]//img[contains(@class, "tour-still")]')->item(0);

    expect($page->query('//section[@data-ref="Tour"]//canvas')->length)->toBe(0)
        ->and($page->query('//section[@data-ref="Tour"]//*[contains(@class, "tour-step")]/h2')->length)->toBe(4)
        ->and($still)->not->toBeNull()
        ->and($still->getAttribute('src'))->toEndWith('/images/site/diente.webp')
        ->and($still->getAttribute('alt'))->toBe('')
        ->and($still->getAttribute('data-origen'))->toBe('dibujada')
        ->and([$still->getAttribute('width'), $still->getAttribute('height')])->toBe(['720', '720'])
        ->and(file_exists(public_path('images/site/diente.webp')))->toBeTrue();
});

/*
 * T042 (refined 2026-11-15): the phone menu of the home carries the coupon of the current promotion
 * that ends first, the same one the big coupon opens with; no other page carries it.
 */
const MENU_COUPON = '//details[contains(@class, "menu-m")]//a[contains(@class, "mn-promo")]';

it('puts the current promotion that ends first in the menu of the home, with WhatsApp asking for it', function () {
    homePromotion('Termina el 20', '2026-11-20');
    homePromotion('Termina el 15', '2026-11-15');
    homePromotion('Ya terminó', '2026-11-10');

    $page = homePage($this);
    $coupon = $page->query(MENU_COUPON)->item(0);

    expect($page->query(MENU_COUPON)->length)->toBe(1)
        ->and(homeText($page, MENU_COUPON.'//b'))->toBe('Termina el 15')
        ->and(homeText($page, MENU_COUPON.'//*[contains(@class, "mn-promo-pct")]'))->toBe('15%')
        ->and($coupon->getAttribute('data-end'))->toBe('2026-11-15')
        ->and($coupon->getAttribute('href'))->toBe('https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode('Hola, me interesa la promoción "Termina el 15".'));
});

it('leaves the coupon out of the menu when no promotion is current, and out of the other pages', function () {
    expect(homePage($this)->query(MENU_COUPON)->length)->toBe(0);

    homePromotion('Vigente', '2026-11-20');

    foreach (['/acerca-de-nosotros', '/galeria'] as $path) {
        expect($this->get($path)->assertOk()->getContent())->not->toContain('mn-promo');
    }
});

it('shows a promotion title with markup as text in the menu coupon (abuse)', function () {
    homePromotion('<img src=x onerror=alert(1)>', '2026-11-20');

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('<img src=x onerror=alert(1)>')
        ->and(homeText(homePage($this), MENU_COUPON.'//b'))->toBe('<img src=x onerror=alert(1)>');
});
