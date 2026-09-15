<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserNameException;
use App\Modules\Users\Domain\ValueObjects\UserName;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P1 - totality. This is THE property of BR-10. Before the fix, create()'s error
// branch built its exception with self::create($first, $last) - the very arguments
// that had just failed validation - so it recursed with no base case until PHP died
// with a Fatal error at roughly 157,900 frames. A Fatal error is not a Throwable, so
// no try/catch could contain it: the request died, and with it the worker.
//
// The property is deliberately weak: it does not say which names are valid, only that
// create() always REACHES a decision. That is exactly what was broken.

it('always terminates, either returning a name or throwing (property)', function () {
    $this->forAll($this->anyUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        try {
            $name = UserName::create($first, $last);

            expect($name)->toBeInstanceOf(UserName::class);
        } catch (UserNameException $e) {
            expect($e)->toBeInstanceOf(UserNameException::class);
        }
    });
});

it('throws a catchable exception for every invalid name (property)', function () {
    $this->forAll($this->invalidUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        expect(fn () => UserName::create($first, $last))->toThrow(UserNameException::class);
    });
});

// P4 - range invariant. The 3-50 bound applies to the COMBINED name, not to each
// field: 'Zoe Wu' is 6 characters, not 3 and 2.

it('keeps every accepted name inside the 3-50 range (property)', function () {
    $this->forAll($this->validUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        $length = mb_strlen(trim(UserName::create($first, $last)->full()));

        expect($length)->toBeGreaterThanOrEqual(3)
            ->and($length)->toBeLessThanOrEqual(50);
    });
});

// P2 - idempotence. formatName() upper-cases the first character and lower-cases the
// rest, so feeding an already-formatted name back in must be a fixed point. If it were
// not, a round-trip through the database would slowly mangle names.

it('treats name formatting as a fixed point (property)', function () {
    $this->forAll($this->validUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        $once = UserName::create($first, $last);
        $twice = UserName::create($once->firstName, $once->lastName);

        expect($twice->firstName)->toBe($once->firstName)
            ->and($twice->lastName)->toBe($once->lastName)
            ->and($twice->full())->toBe($once->full());
    });
});

// P3 - round-trip between the two constructors. Before finding 20 was fixed this only
// held for 2-3 word names: formatName() capitalised per FIELD, so when fromString() drew
// the first/last boundary somewhere else, the capitals moved with it. Per-word
// capitalisation makes full() independent of the boundary, so it now holds for every
// valid name.

it('round-trips through fromString and full (property)', function () {
    $this->forAll($this->validUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        $original = UserName::create($first, $last);

        expect(UserName::fromString($original->full())->full())->toBe($original->full());
    });
});

// P19 - per-word capitalisation (finding 20, defect 1a). Every word of the displayed name
// starts with a capital and continues in lower case. The previous per-field rule produced
// 'Luis Garcia lopez' for almost every staff member with the two surnames usual in Mexico.

it('capitalises every word of the name (property)', function () {
    $this->forAll($this->validUserNameGenerator())->then(function (array $pair) {
        [$first, $last] = $pair;

        foreach (preg_split('/\s+/u', UserName::create($first, $last)->full(), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            expect(mb_substr($word, 0, 1))->toBe(mb_strtoupper(mb_substr($word, 0, 1)))
                ->and(mb_substr($word, 1))->toBe(mb_strtolower(mb_substr($word, 1)));
        }
    });
});

// Finding 20, defect 1b - fixed. These two names used to come back from fromString() with
// their capitals in different places, so the same doctor was written one way on the Users
// screen and another way on the appointments screen.
it('displays the same full name wherever fromString draws the boundary', function (string $first, string $last, string $expected) {
    $original = UserName::create($first, $last);

    expect($original->full())->toBe($expected)
        ->and(UserName::fromString($original->full())->full())->toBe($expected);
})->with([
    'one-word first name, four words total' => ['Ana', 'Garcia Lopez Ruiz', 'Ana Garcia Lopez Ruiz'],
    'three-word first name, five words total' => ['Maria de Jesus', 'Rebolledo Murga', 'Maria De Jesus Rebolledo Murga'],
]);

it('treats the last two words as the surnames, like PatientName', function () {
    $name = UserName::fromString('Maria de Jesus Rebolledo Murga');

    expect($name->firstName)->toBe('Maria De Jesus')
        ->and($name->lastName)->toBe('Rebolledo Murga');
});
