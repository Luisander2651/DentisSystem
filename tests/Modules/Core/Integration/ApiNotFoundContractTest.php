<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, TM4: the "No encontrada" page and its fallback route are for screens. An address
 * of the API that does not exist, or that does not admit the verb, keeps its code and its
 * JSON body, whatever the client says it accepts.
 */

beforeEach(function () {
    config(['app.debug' => false]);
    $this->withoutRateLimiting();
});

it('keeps the 404 and the JSON of an API address that does not exist', function (string $method, string $accept) {
    $response = $this->call($method, '/api/v1/ruta-que-no-existe', server: ['HTTP_ACCEPT' => $accept]);

    $response->assertNotFound();
    $response->assertExactJson(['message' => 'The route api/v1/ruta-que-no-existe could not be found.']);
})->with([
    'GET asking for JSON' => ['GET', 'application/json'],
    'GET from a browser' => ['GET', 'text/html'],
    'POST asking for JSON' => ['POST', 'application/json'],
    'POST from a browser' => ['POST', 'text/html'],
    'DELETE asking for JSON' => ['DELETE', 'application/json'],
]);

it('keeps the 405 and the JSON of an API address that only admits POST', function (string $accept) {
    $response = $this->get('/api/v1/auth/login', ['Accept' => $accept]);

    $response->assertMethodNotAllowed();
    $response->assertExactJson(['message' => 'The GET method is not supported for route api/v1/auth/login. Supported methods: POST.']);
    expect($response->headers->get('Allow'))->toBe('POST');
})->with([
    'asking for JSON' => ['application/json'],
    'from a browser' => ['text/html'],
]);
