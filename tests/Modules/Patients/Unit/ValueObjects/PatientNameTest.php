<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PatientNameException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any combined length within [3, 50] (property)', function () {
    $this->forAll(
        Eris\Generator\choose(1, 24),
        Eris\Generator\choose(1, 24),
    )->then(function (int $firstLen, int $lastLen) {
        // firstLen, lastLen en [1,24] => combinado en [3,49], siempre dentro del rango válido [3,50]
        $combined = $firstLen + 1 + $lastLen;

        $vo = PatientName::create(str_repeat('a', $firstLen), str_repeat('b', $lastLen));

        expect(mb_strlen($vo->full()))->toBe($combined);
    });
});

it('rejects a combined length below the minimum for any short lastName (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 1))->then(function (int $lastLen) {
        // firstName vacío: full() = "" + " " + lastName, trim() deja solo lastName
        expect(fn () => PatientName::create('', str_repeat('b', $lastLen)))
            ->toThrow(PatientNameException::class);
    });
});

it('rejects a combined length above the maximum for any long lastName (property)', function () {
    $this->forAll(Eris\Generator\choose(49, 200))->then(function (int $lastLen) {
        // firstName de longitud 1 + espacio + lastName >= 49 => combinado >= 51
        expect(fn () => PatientName::create('a', str_repeat('b', $lastLen)))
            ->toThrow(PatientNameException::class);
    });
});

it('formats first letter uppercase and rest lowercase (example)', function () {
    $vo = PatientName::create('jOHN', 'dOE');

    expect($vo->firstName)->toBe('John');
    expect($vo->lastName)->toBe('Doe');
    expect($vo->full())->toBe('John Doe');
});

it('rejects a combined length below the minimum (example)', function () {
    expect(fn () => PatientName::create('J', ''))
        ->toThrow(PatientNameException::class);
});

it('fromString treats a 2-word name as 1 nombre + 1 apellido (example)', function () {
    $vo = PatientName::fromString('John Doe');

    expect($vo->firstName)->toBe('John')
        ->and($vo->lastName)->toBe('Doe');
});

it('fromString treats a 3-word name as 1 nombre + 2 apellidos (example)', function () {
    $vo = PatientName::fromString('John Michael Doe');

    expect($vo->firstName)->toBe('John')
        ->and($vo->lastName)->toBe('Michael Doe');
});

it('fromString treats a 4-word name as 2 nombres + 2 apellidos (example)', function () {
    $vo = PatientName::fromString('Ana Maria Garcia Lopez');

    expect($vo->firstName)->toBe('Ana Maria')
        ->and($vo->lastName)->toBe('Garcia Lopez');
});

it('fromString keeps apellidos at 2 words for a 5-word name (example from user)', function () {
    $vo = PatientName::fromString('Maria de Jesus Rebolledo Murga');

    expect($vo->firstName)->toBe('Maria De Jesus')
        ->and($vo->lastName)->toBe('Rebolledo Murga');
});

it('fromString keeps apellidos at 2 words for a 6-word name (example)', function () {
    $vo = PatientName::fromString('ana maria de la cruz hernandez');

    expect($vo->firstName)->toBe('Ana Maria De La')
        ->and($vo->lastName)->toBe('Cruz Hernandez');
});

it('fromString capitalizes every word, not just the first letter of the whole string (property)', function () {
    $this->forAll(Eris\Generator\choose(3, 8))->then(function (int $wordCount) {
        $words = array_map(static fn (int $i): string => 'word'.$i, range(1, $wordCount));
        $fullName = implode(' ', $words);

        $vo = PatientName::fromString($fullName);

        foreach (explode(' ', $vo->firstName.' '.$vo->lastName) as $formattedWord) {
            expect($formattedWord)->toMatch('/^[A-Z][a-z0-9]*$/');
        }

        // los últimos 2 tokens originales siempre terminan como apellido
        expect($vo->lastName)->toBe(ucfirst($words[$wordCount - 2]).' '.ucfirst($words[$wordCount - 1]));
    });
});
