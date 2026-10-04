<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA7 (OB5.b, RS10.a, TM2): with debug off, an unexpected error on a web page
 * answers a generic 500 page, without the exception message, file paths or traces.
 * The API side is covered by the spec 014 tests (GlobalErrorFallbackTest).
 */

beforeEach(function () {
    Route::middleware('web')->get('/_test/boom', function () {
        throw new RuntimeException('internal-detail-7731 in /var/www/html/app/Secret.php');
    });
});

it('answers a generic 500 page without internal details when debug is off (abuse)', function () {
    config(['app.debug' => false]);

    $response = $this->get('/_test/boom');

    $response->assertInternalServerError();
    expect($response->getContent())->not->toContain('internal-detail-7731')
        ->and($response->getContent())->not->toContain('/var/www/html')
        ->and($response->getContent())->not->toContain('RuntimeException')
        ->and($response->getContent())->not->toContain('#0 ');
});
