<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Infrastructure\Qr\ChillerlanQrCodeGenerator;
use Eris\Generators;
use Illuminate\Support\Str;
use Tests\Support\DecodesQrSvg;
use Tests\Support\UsesEris;

uses(UsesEris::class, DecodesQrSvg::class);

/*
 * Spec 024 (CA1, TM3): for any appointment id, the QR code reads back exactly that id; and the ids
 * the clinic hands out are random version 4 UUIDs, so a QR code cannot be made by guessing one.
 */

it('reads back exactly the appointment id for any id (property)', function () {
    $generator = new ChillerlanQrCodeGenerator;

    $this->limitTo(15)->forAll(
        Generators::constant(null),
    )->then(function () use ($generator): void {
        $id = (string) Str::uuid();

        expect($this->decodeQrSvg($generator->svg($id)))->toBe($id);
    });
})->skip('024: pendiente de T020');

it('hands out random version 4 appointment ids (property)', function () {
    $this->forAll(
        Generators::constant(null),
    )->then(function (): void {
        $id = AppointmentId::random()->value;

        expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    });
});
