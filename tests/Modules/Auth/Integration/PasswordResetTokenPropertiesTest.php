<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;
use Tests\Support\AuthGenerators;
use Tests\Support\UsesEris;

uses(AuthIntegrationTestCase::class, UsesEris::class, AuthGenerators::class);

// P2 - invariant: shape, TTL and status of every issued token.

it('always issues a 40-character alphanumeric token stored with a 900s TTL and PENDING status (property)', function () {
    $this->limitTo(25);

    $this->forAll($this->validEmailGenerator())->then(function (string $email) {
        $token = $this->generateResetTokenFor($email);

        expect($token)->toHaveLength(40);
        expect($token)->toMatch('/^[A-Za-z0-9]{40}$/');

        $ttl = Redis::ttl($this->resetTokenKey($token));
        expect($ttl)->toBeGreaterThan(890)->toBeLessThanOrEqual(900);

        $payload = json_decode(Redis::get($this->resetTokenKey($token)), true);
        expect($payload['status'])->toBe('PENDING');

        $this->forgetResetToken($token);
    });
});

// P4 - round-trip: the email survives the JSON encode/decode cycle unchanged.

it('round-trips the email through the Redis payload without loss (property)', function () {
    $this->limitTo(25);

    $this->forAll($this->validEmailGenerator())->then(function (string $email) {
        $token = $this->generateResetTokenFor($email);

        $payload = json_decode(Redis::get($this->resetTokenKey($token)), true);
        expect($payload['email'])->toBe($email);

        $this->forgetResetToken($token);
    });
});

// P3 - uniqueness invariant across a sequence of issues.

it('never issues the same token twice (property)', function () {
    $this->limitTo(10);

    $this->forAll(Eris\Generator\choose(2, 15))->then(function (int $howMany) {
        $tokens = [];

        for ($i = 0; $i < $howMany; $i++) {
            $tokens[] = $this->generateResetTokenFor("reuse-{$i}@example.com");
        }

        expect(array_unique($tokens))->toHaveCount($howMany);

        foreach ($tokens as $token) {
            $this->forgetResetToken($token);
        }
    });
});
