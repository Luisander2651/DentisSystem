<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\AllergiesException;
use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\MedicationsException;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Allergies;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Medications;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('Allergies accepts any array whose elements are all strings (property)', function () {
    $this->forAll(Eris\Generator\elements([
        [],
        ['Penicilina'],
        ['Penicilina', 'Latex'],
        ['a', 'b', 'c', 'd'],
    ]))->then(function (array $valid) {
        expect(Allergies::fromNullableArray($valid)->value)->toBe(array_values($valid));
    });
});

it('Allergies rejects any array containing a non-string element (property)', function () {
    $this->forAll(Eris\Generator\elements([
        [123],
        ['Penicilina', 456],
        [null],
        [['nested']],
        [true],
    ]))->then(function (array $invalid) {
        expect(fn () => Allergies::fromNullableArray($invalid))->toThrow(AllergiesException::class);
    });
});

it('Medications accepts any array whose elements are all strings (property)', function () {
    $this->forAll(Eris\Generator\elements([
        [],
        ['Ibuprofeno'],
        ['Ibuprofeno', 'Paracetamol'],
    ]))->then(function (array $valid) {
        expect(Medications::fromNullableArray($valid)->value)->toBe(array_values($valid));
    });
});

it('Medications rejects any array containing a non-string element (property)', function () {
    $this->forAll(Eris\Generator\elements([
        [123],
        ['Ibuprofeno', 4.5],
        [null],
    ]))->then(function (array $invalid) {
        expect(fn () => Medications::fromNullableArray($invalid))->toThrow(MedicationsException::class);
    });
});

it('treats null as absent for both (example)', function () {
    expect(Allergies::fromNullableArray(null)->value)->toBeNull();
    expect(Medications::fromNullableArray(null)->value)->toBeNull();
});

it('re-indexes a non-sequential array of strings (example)', function () {
    $vo = Allergies::fromNullableArray([5 => 'Latex', 2 => 'Polen']);

    expect($vo->value)->toBe(['Latex', 'Polen']);
});
