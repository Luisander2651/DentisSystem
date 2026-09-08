<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use Mockery\MockInterface;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

// --- BR-12: throttle:api caps unauthenticated traffic at 10 req/min per IP ---
// This file deliberately does NOT call withoutRateLimiting().

it('returns 429 on the eleventh unauthenticated login attempt from the same IP (BR-12)', function () {
    $payload = $this->validLoginPayload();

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson($this->loginUrl(), $payload)
            ->assertUnauthorized();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->postJson($this->loginUrl(), $payload)
        ->assertStatus(429);
});

it('throttles per IP, so a different address is unaffected (BR-12)', function () {
    $payload = $this->validLoginPayload();

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson($this->loginUrl(), $payload)
            ->assertUnauthorized();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->postJson($this->loginUrl(), $payload)
        ->assertUnauthorized();
});

// --- BR-15: internal exception details never reach the client ---

it('returns a generic 500 without the internal exception message (BR-15)', function () {
    // Every Auth use case and service is final, so the failure is injected at the
    // repository interface, the one seam the container can actually substitute.
    $this->mock(PatientsRepositoryInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('findByEmailExcludingId')
            ->andThrow(new RuntimeException('SQLSTATE[42P01]: undefined_table: internal detail'));
    });

    $response = $this->postJson($this->registerUrl(), $this->validRegisterPayload());

    $response->assertStatus(500)->assertExactJson(['error' => 'Internal server error']);
    expect($response->getContent())->not->toContain('SQLSTATE');
    expect($response->getContent())->not->toContain('undefined_table');
});
