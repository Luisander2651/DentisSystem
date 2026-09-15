<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\EmailException;
use App\Modules\Users\Domain\ValueObjects\UserEmail;

it('accepts a well-formed address verbatim', function (string $email) {
    expect((new UserEmail($email))->value)->toBe($email);
})->with(['a@b.co', 'maria.jose+tag@mail.example.com', 'st4ff@clinica-dentissa.mx']);

it('does not normalise case', function () {
    // Worth pinning: BR-2 checks uniqueness with an exact comparison, so Ana@x.com and
    // ana@x.com are two different staff members as far as this module is concerned.
    expect((new UserEmail('Ana@Example.COM'))->value)->toBe('Ana@Example.COM');
});

it('rejects a malformed address', function (string $email) {
    expect(fn () => new UserEmail($email))->toThrow(EmailException::class);
})->with(['', 'not-an-email', '@example.com', 'user@', 'user@.com']);
