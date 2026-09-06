<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\ContactEmailException;
use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\EmailException;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('PatientEmail accepts any valid email format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'john@example.com',
        'john.doe@example.co.uk',
        'j+tag@example.io',
        'a@b.co',
    ]))->then(function (string $valid) {
        expect(PatientEmail::fromString($valid)->value)->toBe($valid);
    });
});

it('PatientEmail rejects any invalid email format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'not-an-email',
        'missing-at.com',
        '@missing-local.com',
        'missing-domain@',
        '',
        'spaces in@email.com',
    ]))->then(function (string $invalid) {
        expect(fn () => new PatientEmail($invalid))->toThrow(EmailException::class);
    });
});

it('ContactEmail accepts any valid email format, nullable (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'contact@example.com',
        'a.b+c@example.co.uk',
    ]))->then(function (string $valid) {
        expect(ContactEmail::fromNullable($valid)->value)->toBe($valid);
    });

    expect(ContactEmail::fromNullable(null)->value)->toBeNull();
    expect(ContactEmail::fromNullable('')->value)->toBeNull();
});

it('ContactEmail rejects any invalid email format (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'not-an-email',
        '@missing-local.com',
        'missing-domain@',
    ]))->then(function (string $invalid) {
        expect(fn () => ContactEmail::fromNullable($invalid))->toThrow(ContactEmailException::class);
    });
});

it('accepts a known valid email (example)', function () {
    expect(PatientEmail::fromString('patient@dentissa.com')->value)->toBe('patient@dentissa.com');
});

it('rejects a known invalid email (example)', function () {
    expect(fn () => new PatientEmail('invalid'))->toThrow(EmailException::class);
});
