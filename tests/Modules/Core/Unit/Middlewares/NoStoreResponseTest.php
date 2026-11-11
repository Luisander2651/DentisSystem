<?php

declare(strict_types=1);

use App\Core\Middlewares\NoStoreResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA20, TM10): no answer of the portal may be kept by a browser or a proxy.
 */

it('asks browsers and proxies not to keep the answer', function (Closure $answer) {
    $response = (new NoStoreResponse)->handle(Request::create('/api/v1/portal/appointments'), $answer);

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
})->with([
    // The parameter is typed Closure, so Pest hands each closure over as it is.
    'a success' => [fn (): Response => new Response('ok')],
    'an error' => [fn (): JsonResponse => new JsonResponse(['error' => 'Appointment not found.'], 404)],
]);
