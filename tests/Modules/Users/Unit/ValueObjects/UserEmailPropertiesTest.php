<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\EmailException;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P5 - dichotomy. For every string the constructor either returns a UserEmail whose
// value passes FILTER_VALIDATE_EMAIL, or throws EmailException. Never both, never
// neither: there is no third outcome such as a silently normalised address.

it('accepts every address that passes FILTER_VALIDATE_EMAIL (property)', function () {
    $this->forAll($this->validUserEmailGenerator())->then(function (string $email) {
        $vo = new UserEmail($email);

        expect($vo->value)->toBe($email)
            ->and(filter_var($vo->value, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
    });
});

it('rejects every address FILTER_VALIDATE_EMAIL refuses (property)', function () {
    $this->forAll($this->invalidUserEmailGenerator())->then(function (string $email) {
        expect(fn () => new UserEmail($email))->toThrow(EmailException::class);
    });
});

it('preserves the address verbatim through fromString (property)', function () {
    $this->forAll($this->validUserEmailGenerator())->then(function (string $email) {
        expect(UserEmail::fromString($email)->value)->toBe($email);
    });
});
