<?php

declare(strict_types=1);

use App\Modules\Users\Domain\ValueObjects\UserId;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P11. UuidIdentifier throws InvalidArgumentException, which does NOT descend from the
// module's ValueObjectsException umbrella - that mismatch is finding 10, and it is what
// turned a malformed id in the route into a 500. The VO's own contract is asserted here;
// the HTTP translation to 400 is asserted in the integration suite (BR-19).

it('rejects every malformed identifier (property)', function () {
    $this->forAll($this->malformedUuidGenerator())->then(function (string $candidate) {
        expect(fn () => new UserId($candidate))->toThrow(InvalidArgumentException::class);
    });
});

it('reconstructs an equal identifier from its own value (property)', function () {
    $this->forAll($this->userStatusGenerator())->then(function () {
        $id = UserId::random();

        expect($id->equals(new UserId($id->value)))->toBeTrue()
            ->and($id->equals(UserId::random()))->toBeFalse();
    });
});
