<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserNameException;
use App\Modules\Users\Domain\ValueObjects\UserName;

it('capitalises the first letter and lowercases the rest', function () {
    $name = UserName::create('aNA', 'rUIZ');

    expect($name->firstName)->toBe('Ana')
        ->and($name->lastName)->toBe('Ruiz')
        ->and($name->full())->toBe('Ana Ruiz')
        ->and((string) $name)->toBe('Ana Ruiz');
});

it('capitalises each word of a compound name', function () {
    // Finding 20, defect 1a: this used to produce 'Luis Garcia lopez'.
    expect(UserName::create('luis', 'GARCIA LOPEZ')->full())->toBe('Luis Garcia Lopez');
});

it('does not leave a trailing space when the surname is empty', function () {
    // Only reachable with data created before BR-12 made last_name required.
    expect(UserName::create('Ana', '')->full())->toBe('Ana');
});

it('trims the surrounding whitespace of each field', function () {
    expect(UserName::create('  Ana  ', '  Ruiz  ')->full())->toBe('Ana Ruiz');
});

it('measures the 3-50 bound against the combined name, not each field', function () {
    // 'A B' is exactly 3 characters, so it sits on the minimum and is accepted even
    // though neither field reaches 3 on its own.
    expect(UserName::create('A', 'B')->full())->toBe('A B');
});

it('rejects a combined name below the minimum', function (string $first, string $last) {
    // BR-10: before the fix this did not raise a catchable exception - create() recursed
    // into itself until PHP died, taking the worker with it.
    expect(fn () => UserName::create($first, $last))->toThrow(UserNameException::class);
})->with([
    'both empty' => ['', ''],
    'only whitespace' => ['  ', '   '],
    'single character' => ['A', ''],
]);

it('rejects a combined name above the maximum', function () {
    expect(fn () => UserName::create(str_repeat('x', 30), str_repeat('y', 30)))
        ->toThrow(UserNameException::class);
});

it('reports the offending name in the exception message', function () {
    expect(fn () => UserName::create('A', ''))
        ->toThrow(UserNameException::class, 'The username length is invalid: A.');
});

it('splits a full name into first and last', function (string $full, string $first, string $last) {
    $name = UserName::fromString($full);

    expect($name->firstName)->toBe($first)
        ->and($name->lastName)->toBe($last);
})->with([
    'two words' => ['Ana Ruiz', 'Ana', 'Ruiz'],
    'three words, the last two are the surnames' => ['Ana Garcia Lopez', 'Ana', 'Garcia Lopez'],
    'four words, the last two are the surnames' => ['Ana Maria Garcia Lopez', 'Ana Maria', 'Garcia Lopez'],
    'five words, everything before the surnames is the first name' => ['Maria de Jesus Rebolledo Murga', 'Maria De Jesus', 'Rebolledo Murga'],
]);

it('rejects a full name with fewer than two words', function (string $full) {
    expect(fn () => UserName::fromString($full))->toThrow(UserNameException::class);
})->with(['Ana', '', '   ']);
