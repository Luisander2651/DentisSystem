<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserStatusException;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P10 - dichotomy. Every accepted status is active XOR inactive: never both (which
// would make authorisation checks contradict each other) and never neither (which
// would let an account slip past both branches of every status check).

it('makes every accepted status exactly one of active or inactive (property)', function () {
    $this->forAll($this->userStatusGenerator())->then(function (string $status) {
        $vo = UserStatus::fromString($status);

        expect($vo->isActive() !== $vo->isInactive())->toBeTrue()
            ->and($vo->value)->toBe($status);
    });
});

it('rejects every value outside the two allowed statuses (property)', function () {
    $this->forAll($this->invalidUserStatusGenerator())->then(function (string $status) {
        expect(fn () => UserStatus::fromString($status))->toThrow(UserStatusException::class);
    });
});
