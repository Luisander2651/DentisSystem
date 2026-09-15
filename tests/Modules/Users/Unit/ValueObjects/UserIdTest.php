<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Domain\ValueObjects\UserId;
use Illuminate\Support\Str;

it('accepts a valid uuid', function () {
    $uuid = (string) Str::uuid();

    expect((new UserId($uuid))->value)->toBe($uuid);
});

it('generates a fresh identifier each time', function () {
    expect(UserId::random()->value)->not->toBe(UserId::random()->value);
});

it('compares by value', function () {
    $id = UserId::random();

    expect($id->equals(new UserId($id->value)))->toBeTrue();
});

it('throws outside the module exception umbrella', function () {
    // Finding 10 in the open: UuidIdentifier lives in App\Core and throws a plain
    // InvalidArgumentException, which does NOT descend from ValueObjectsException. That
    // is why a malformed id used to reach the generic handler and answer 500, and why the
    // controllers now translate it to 400 themselves (BR-19). App\Core is shared with
    // other modules, so its hierarchy was deliberately left alone.
    expect(fn () => new UserId('not-a-uuid'))->toThrow(InvalidArgumentException::class);

    try {
        new UserId('not-a-uuid');
    } catch (InvalidArgumentException $e) {
        expect($e)->not->toBeInstanceOf(ValueObjectsException::class);
    }
});
