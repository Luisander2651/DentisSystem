<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Qr\ChillerlanQrCodeGenerator;
use chillerlan\QRCode\Common\EccLevel;
use Tests\Support\DecodesQrSvg;

uses(DecodesQrSvg::class);

/*
 * Spec 024 (CA1, CA2, TM6, TM8): the QR code of an appointment is an SVG document that holds the
 * given text and nothing else, with at least error correction M, without scripts nor links, and
 * cheap enough to draw on every request.
 */

const QR_GENERATOR_APPOINTMENT_ID = '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34';

it('draws an SVG document', function () {
    $svg = (new ChillerlanQrCodeGenerator)->svg(QR_GENERATOR_APPOINTMENT_ID);

    expect($svg)->toStartWith('<?xml')
        ->and($svg)->toContain('<svg');
})->skip('024: pendiente de T020');

it('holds exactly the given text, without prefix nor spaces', function () {
    $svg = (new ChillerlanQrCodeGenerator)->svg(QR_GENERATOR_APPOINTMENT_ID);

    expect($this->decodeQrSvg($svg))->toBe(QR_GENERATOR_APPOINTMENT_ID);
})->skip('024: pendiente de T020');

it('draws the same code every time for the same text', function () {
    $generator = new ChillerlanQrCodeGenerator;

    expect($generator->svg(QR_GENERATOR_APPOINTMENT_ID))->toBe($generator->svg(QR_GENERATOR_APPOINTMENT_ID));
})->skip('024: pendiente de T020');

it('uses error correction M', function () {
    $result = $this->decodeQrSvgResult((new ChillerlanQrCodeGenerator)->svg(QR_GENERATOR_APPOINTMENT_ID));

    expect($result->eccLevel->getLevel())->toBe(EccLevel::M);
})->skip('024: pendiente de T020');

it('carries no script nor link that could run when the file is opened (abuse)', function () {
    $svg = (new ChillerlanQrCodeGenerator)->svg(QR_GENERATOR_APPOINTMENT_ID);

    expect(stripos($svg, '<script'))->toBeFalse()
        ->and(stripos($svg, 'href'))->toBeFalse()
        ->and(stripos($svg, 'xlink'))->toBeFalse()
        ->and(stripos($svg, 'onload'))->toBeFalse();
})->skip('024: pendiente de T020');

it('draws a code in less than 150 ms', function () {
    $generator = new ChillerlanQrCodeGenerator;
    $generator->svg(QR_GENERATOR_APPOINTMENT_ID);

    $start = hrtime(true);
    $generator->svg(QR_GENERATOR_APPOINTMENT_ID);
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    expect($elapsedMs)->toBeLessThan(150);
})->skip('024: pendiente de T020');
