<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserStatusException;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

it('builds the two allowed statuses', function () {
    expect(UserStatus::active()->value)->toBe('active')
        ->and(UserStatus::inactive()->value)->toBe('inactive');
});

it('answers the matching predicate', function () {
    expect(UserStatus::active()->isActive())->toBeTrue()
        ->and(UserStatus::active()->isInactive())->toBeFalse()
        ->and(UserStatus::inactive()->isInactive())->toBeTrue();
});

it('is case sensitive, unlike the role vocabulary', function () {
    // Deliberate asymmetry, worth pinning: `status` is an enum column this application
    // writes, while a role literal comes from a table row a human may have typed.
    expect(fn () => UserStatus::fromString('Active'))->toThrow(UserStatusException::class);
});

it('rejects anything outside the two statuses', function (string $status) {
    expect(fn () => UserStatus::fromString($status))->toThrow(UserStatusException::class);
})->with(['', 'activo', 'enabled', 'deleted']);
