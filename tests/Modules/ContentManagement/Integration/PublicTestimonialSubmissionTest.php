<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SubmitPublicTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\SubmitPublicTestimonialUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 027: a visitor leaves an opinion from the public site. It always arrives hidden, with the
 * server's time, and the clinic publishes it from "Contenido". The log of each opinion received
 * keeps only its id and the consent, never the name or the text (TM4, TM6).
 */

it('keeps a public opinion hidden, with the time of the server, and logs only its id and the consent', function () {
    $this->freezeTime();
    Log::spy();

    app(SubmitPublicTestimonialUseCase::class)->execute(new SubmitPublicTestimonialDTO(
        author: 'Mariana López',
        description: 'Me atendieron con mucha paciencia y el resultado de mi limpieza fue excelente.',
    ));

    $row = TestimonialModel::query()->sole();

    expect($row->status)->toBe('oculto')
        ->and($row->author)->toBe('Mariana López')
        ->and($row->created_at->toDateTimeString())->toBe(now()->toDateTimeString());

    Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $message === 'public_testimonial.received'
        && $context === ['testimonial_id' => $row->id, 'consent' => true]);
});

it('keeps a name of eighty characters of four bytes each (A9)', function () {
    app(SubmitPublicTestimonialUseCase::class)->execute(new SubmitPublicTestimonialDTO(
        author: str_repeat('😀', 80),
        description: 'Una opinión con un nombre hecho solo de emoji, que mide ochenta caracteres.',
    ));

    expect(TestimonialModel::query()->sole()->author)->toBe(str_repeat('😀', 80));
});

/** A valid submission of the form, with what each case changes. */
function opinionForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mariana López',
        'opinion' => 'Me atendieron con mucha paciencia y el resultado de mi limpieza fue excelente.',
        'consent' => '1',
        'website' => '',
    ], $overrides);
}

beforeEach(function () {
    RateLimiter::clear('public-testimonials:127.0.0.1');
});

it('saves a valid opinion hidden and comes back to the page with the thanks (CA2)', function () {
    $this->post('/opinion', opinionForm())
        ->assertRedirect('/opinion')
        ->assertSessionHas('opinion_sent', true);

    expect(TestimonialModel::query()->sole()->status)->toBe('oculto');
});

it('accepts the name and the opinion at the edges of their length (CA2)', function (array $fields) {
    $this->post('/opinion', opinionForm($fields))->assertRedirect('/opinion')->assertSessionHasNoErrors();

    expect(TestimonialModel::query()->count())->toBe(1);
})->with([
    'name of 2' => [['name' => 'Al']],
    'name of 80' => [['name' => str_repeat('a', 80)]],
    'opinion of 20' => [['opinion' => str_repeat('b', 20)]],
    'opinion of 255' => [['opinion' => str_repeat('c', 255)]],
    'opinion of 255 with line breaks sent as CRLF (A10)' => [['opinion' => str_repeat("línea\r\n", 42).'abc']],
]);

it('saves nothing and comes back with a message beside each wrong field, keeping what was written (CA3)', function (array $fields, string $error) {
    $this->from('/opinion')->post('/opinion', opinionForm($fields))
        ->assertRedirect('/opinion')
        ->assertSessionHasErrors($error)
        ->assertSessionHasInput($error === 'name' ? 'opinion' : 'name');

    expect(TestimonialModel::query()->count())->toBe(0);
})->with([
    'name of 1' => [['name' => 'A'], 'name'],
    'name of 81' => [['name' => str_repeat('a', 81)], 'name'],
    'no name' => [['name' => null], 'name'],
    'blank name' => [['name' => '    '], 'name'],
    'name as a list' => [['name' => ['Mariana']], 'name'],
    'opinion of 19' => [['opinion' => str_repeat('b', 19)], 'opinion'],
    'opinion of 256' => [['opinion' => str_repeat('c', 256)], 'opinion'],
    'no opinion' => [['opinion' => ''], 'opinion'],
    'opinion as a list' => [['opinion' => ['texto']], 'opinion'],
    'no consent' => [['consent' => null], 'consent'],
]);

it('writes the error messages in Spanish (CA3)', function () {
    $this->post('/opinion', opinionForm(['name' => '', 'opinion' => 'corta', 'consent' => null]))
        ->assertSessionHasErrors([
            'name' => 'Escribe tu nombre como quieres que aparezca.',
            'opinion' => 'Tu opinión debe tener al menos 20 caracteres.',
            'consent' => 'Para enviarla, acepta que publiquemos tu nombre y tu opinión.',
        ]);
});

it('ignores a status, a date or any other field sent with the form: the opinion arrives hidden (CA8, TM4)', function () {
    $this->freezeTime();

    $this->post('/opinion', opinionForm(['status' => 'visible', 'created_at' => '2020-01-01 00:00:00', 'id' => 999]))->assertRedirect('/opinion');

    $row = TestimonialModel::query()->sole();

    expect($row->status)->toBe('oculto')
        ->and($row->id)->not->toBe(999)
        ->and($row->created_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('keeps hidden an opinion sent by a member of the staff with a session: the page gives no special right', function () {
    $this->actingAsAdmin();

    $this->post('/opinion', opinionForm())->assertRedirect('/opinion');

    expect(TestimonialModel::query()->sole()->status)->toBe('oculto');
});

it('answers a filled trap field like a valid submission but saves nothing (CA6, TM2)', function () {
    $this->post('/opinion', opinionForm(['website' => 'https://spam.example']))
        ->assertRedirect('/opinion')
        ->assertSessionHas('opinion_sent', true);

    expect(TestimonialModel::query()->count())->toBe(0);
});

it('refuses a fourth accepted submission in ten minutes from the same connection, and lets it through after (CA5, TM1)', function () {
    foreach (range(1, 2) as $_) {
        $this->post('/opinion', opinionForm())->assertRedirect('/opinion');
    }
    $this->post('/opinion', opinionForm(['website' => 'https://spam.example']))->assertRedirect('/opinion');

    $this->post('/opinion', opinionForm())
        ->assertStatus(429)
        ->assertSee('Enviaste varias opiniones seguidas. Inténtalo de nuevo en unos minutos.');

    expect(TestimonialModel::query()->count())->toBe(2);

    $this->travel(11)->minutes();

    $this->post('/opinion', opinionForm())->assertRedirect('/opinion');
    expect(TestimonialModel::query()->count())->toBe(3);
});

it('does not count submissions with errors for the limit (CA5)', function () {
    foreach (range(1, 4) as $_) {
        $this->post('/opinion', opinionForm(['opinion' => 'corta']))->assertSessionHasErrors('opinion');
    }

    $this->post('/opinion', opinionForm())->assertRedirect('/opinion')->assertSessionHasNoErrors();
    expect(TestimonialModel::query()->count())->toBe(1);
});

it('comes back with a message of its own, never the internal one, when the opinion cannot be saved (P7)', function () {
    $this->mock(TestimonialRepositoryInterface::class)
        ->shouldReceive('save')->andThrow(new RuntimeException('SQLSTATE[08006] connection to db failed'));
    Log::spy();

    $this->from('/opinion')->post('/opinion', opinionForm())
        ->assertRedirect('/opinion')
        ->assertSessionHasErrors(['form' => 'No pudimos guardar tu opinión. Inténtalo de nuevo en un momento.'])
        ->assertSessionMissing('opinion_sent');

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'public_testimonial.save_failed'
        && $context === ['exception' => RuntimeException::class]);
});

it('refuses a submission without the token of the session outside the test environment (CA9, TM5)', function () {
    $this->app['env'] = 'local';

    $this->post('/opinion', opinionForm())->assertStatus(419);

    expect(TestimonialModel::query()->count())->toBe(0);
});

/**
 * @return list<string>
 */
function publicTestimonialAuthors(ContentManagementIntegrationTestCase $test, string $query = ''): array
{
    $data = $test->getJson('/api/v1/public/testimonials'.$query)->assertOk()->json();

    return array_column(isset($data['author']) ? [$data] : $data, 'author');
}

it('lists only the visible testimonials in the public API, never one just sent nor any hidden one (CA11, TM7)', function () {
    $this->withoutMiddleware(IlluminateRoutingMiddlewareThrottleRequests::class);
    TestimonialModel::query()->create(['author' => 'Publicado', 'description' => 'Una opinión ya revisada y publicada por la clínica.', 'status' => 'visible']);
    TestimonialModel::query()->create(['author' => 'Oculto', 'description' => 'Una opinión que la clínica ocultó en Contenido.', 'status' => 'oculto']);
    $this->post('/opinion', opinionForm(['name' => 'Recién enviada']))->assertRedirect('/opinion');

    expect(publicTestimonialAuthors($this))->toBe(['Publicado'])
        ->and(publicTestimonialAuthors($this, '?status=oculto'))->toBe(['Publicado']);
});

it('keeps listing the hidden ones to the staff, who publishes them (CA11, CA4)', function () {
    $this->withoutMiddleware(IlluminateRoutingMiddlewareThrottleRequests::class);
    $this->post('/opinion', opinionForm(['name' => 'Recién enviada']))->assertRedirect('/opinion');
    $this->actingAsAdmin();

    $one = $this->getJson('/api/v1/testimonials')->assertOk()->json();

    TestimonialModel::query()->create(['author' => 'Publicado', 'description' => 'Una opinión ya revisada y publicada por la clínica.', 'status' => 'visible']);
    $many = $this->getJson('/api/v1/testimonials')->assertOk()->json();

    expect($one['author'])->toBe('Recién enviada')
        ->and($one['status'])->toBe('oculto')
        ->and(array_column($many, 'status', 'author'))->toBe(['Recién enviada' => 'oculto', 'Publicado' => 'visible']);
});

function opinionPage(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

it('serves the page with the name, the opinion, the consent with the privacy notice and a trap no person sees (CA1, CA10)', function () {
    $this->withoutVite();
    $page = opinionPage($this->get('/opinion')->assertOk()->getContent());
    $form = $page->query('//form[@method="post"][@action="'.route('opinion.store').'"]');
    $field = fn (string $query): ?DOMElement => $page->query($query, $form->item(0))->item(0);

    expect($form)->toHaveCount(1)
        ->and($page->query('//h1')->item(0)->textContent)->toContain('Deja tu opinión')
        ->and($field('.//input[@name="_token"]'))->not->toBeNull()
        ->and($field('.//label[@for="op-name"]')?->textContent)->toContain('Tu nombre')
        ->and($field('.//input[@id="op-name"][@name="name"][@required][@maxlength="80"]'))->not->toBeNull()
        ->and($field('.//label[@for="op-text"]')?->textContent)->toContain('Tu opinión')
        ->and($field('.//textarea[@id="op-text"][@name="opinion"][@required][@maxlength="255"]'))->not->toBeNull()
        ->and($field('.//input[@type="checkbox"][@name="consent"][@required]'))->not->toBeNull()
        ->and($field('.//a[@href="'.route('aviso-privacidad').'"]'))->not->toBeNull()
        ->and($field('.//*[@aria-hidden="true"]//input[@name="website"][@tabindex="-1"][@autocomplete="off"]'))->not->toBeNull()
        ->and($field('.//button[@type="submit"]')?->textContent)->toContain('Enviar mi opinión');
});

it('shows each error beside its field, tied to it, with what was written (CA3, CA10)', function () {
    $this->withoutVite();
    $this->from('/opinion')->post('/opinion', opinionForm(['opinion' => 'corta', 'consent' => null]));
    $page = opinionPage($this->get('/opinion')->getContent());
    $text = $page->query('//textarea[@id="op-text"]')->item(0);

    expect($text->getAttribute('aria-invalid'))->toBe('true')
        ->and($page->query('//*[@id="'.explode(' ', $text->getAttribute('aria-describedby'))[0].'"]')->item(0)?->textContent)->toContain('al menos 20 caracteres')
        ->and($text->textContent)->toBe('corta')
        ->and($page->query('//input[@id="op-name"]')->item(0)->getAttribute('value'))->toBe('Mariana López')
        ->and($page->query('//input[@name="consent"][@aria-invalid="true"]'))->toHaveCount(1);
});

it('thanks the visitor in a status region in place of the form once the opinion is sent (CA2, CA10)', function () {
    $this->withoutVite();
    $this->post('/opinion', opinionForm());
    $page = opinionPage($this->get('/opinion')->getContent());

    expect($page->query('//form[@action="'.route('opinion.store').'"]'))->toHaveCount(0)
        ->and($page->query('//*[@role="status"]//*[@tabindex="-1"][contains(., "Gracias")]'))->toHaveCount(1)
        ->and($page->query('//*[@role="status"]')->item(0)->textContent)->toContain('la publicaremos cuando la revisemos')
        ->and($page->query('//*[@role="status"]//a[@href="'.route('inicio').'"]'))->toHaveCount(1);
});

it('says on the page when the opinion could not be saved (P7)', function () {
    $this->withoutVite();
    $this->mock(TestimonialRepositoryInterface::class)->shouldReceive('save')->andThrow(new RuntimeException('boom'));
    $this->post('/opinion', opinionForm());

    $html = $this->get('/opinion')->getContent();

    expect(opinionPage($html)->query('//*[@role="alert"][contains(., "No pudimos guardar tu opinión")]'))->toHaveCount(1)
        ->and($html)->not->toContain('boom');
});

it('keeps the page of opinions out of search engines and out of the sitemap (CA9)', function () {
    $this->withoutVite();
    $page = opinionPage($this->get('/opinion')->getContent());

    expect($page->query('//head/meta[@name="robots"][@content="noindex"]'))->toHaveCount(1)
        ->and($this->get('/sitemap.xml')->getContent())->not->toContain('/opinion');
});

it('links "Dejar mi opinión" to the page of opinions also when there are testimonials (CA1)', function () {
    $this->withoutVite();
    TestimonialModel::query()->create(['author' => 'Publicado', 'description' => 'Una opinión ya revisada y publicada por la clínica.', 'status' => 'visible']);

    $page = opinionPage($this->get('/')->assertOk()->getContent());

    expect($page->query('//section[@id="testimonios"]//a[@href="'.route('opinion').'"][contains(., "Dejar mi opinión")]'))->toHaveCount(1)
        ->and($page->query('//section[@id="testimonios"]//a[starts-with(@href, "https://wa.me/")][contains(., "opinión")]'))->toHaveCount(0);
});

it('shows a published opinion with markup as text on the home, never as markup (CA7, TM3)', function () {
    $this->withoutVite();
    $this->post('/opinion', opinionForm(['name' => '<script>alert(1)</script>Ana', 'opinion' => '<img src=x onerror=alert(1)> Excelente atención en mi limpieza dental.']));
    TestimonialModel::query()->update(['status' => 'visible']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->not->toContain('<img src=x onerror')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;Ana');
});

it('passes the name and the opinion through escapeHtml wherever the screen of Contenido writes them (CA7, TM3)', function () {
    $script = (string) file_get_contents(resource_path('js/pages/contenido/testimonios/index.js'));
    preg_match_all("/'\s*,\s*([^,']*\b(?:author|description)\b[^,']*)\s*,\s*'/", $script, $joined);

    expect($joined[1])->not->toBeEmpty();

    foreach ($joined[1] as $expression) {
        expect(trim($expression))->toStartWith('escapeHtml(');
    }
});
